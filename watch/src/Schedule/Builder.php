<?php

namespace Watch\Schedule;

use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue as ScheduleIssue;
use Watch\Schedule\Model\Link as ScheduleLink;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Schedule;
use Watch\Subject\Model\Issue as SubjectIssue;
use Watch\Subject\Model\Link as SubjectLink;

class Builder
{
    protected Schedule|null $schedule = null;

    /**
     * @param Context $context
     * @param SubjectIssue[] $issues
     * @param SubjectLink[] $links
     * @param string[] $milestones
     * @param Mapper $mapper
     * @param LimitStrategy|null $limitStrategy
     * @param ScheduleStrategy|null $scheduleStrategy
     */
    public function __construct(
        protected readonly Context $context,
        protected readonly array $issues,
        protected readonly array $links,
        protected readonly array $milestones,
        private readonly Mapper $mapper,
        private readonly LimitStrategy|null $limitStrategy = null,
        private readonly ScheduleStrategy|null $scheduleStrategy = null,
    )
    {
    }

    public function run(): self
    {
        $this->schedule = new Schedule();
        return $this;
    }

    public function release(): Schedule
    {
        return $this->schedule;
    }

    public function addMilestone(): self
    {
        $nodes = array_reduce(
            array_map(
                fn(SubjectIssue $issue) => new ScheduleIssue($issue->key, $issue->duration, [
                    'begin' => $issue->begin,
                    'end' => $issue->end,
                    'state' => $this->mapper->getIssueState($issue->status),
                ]),
                $this->issues,
            ),
            fn(array $acc, Node $node) => [...$acc, $node->name => $node],
            []
        );

        $milestones = array_reduce(
            array_map(
                fn(string $milestone) => new Milestone($milestone),
                $this->milestones,
            ),
            fn(array $acc, Milestone $milestone) => [...$acc, $milestone->name => $milestone],
            []
        );
        $finalMilestone = reset($milestones);

        foreach ($this->issues as $issue) {
            $outgoingLinks = array_filter(
                $this->links,
                fn($link) => $link->from === $issue->key,
            );
            foreach ($outgoingLinks as $link) {
                $nodes[$link->to]->follow(
                    $nodes[$link->from],
                    $this->mapper->getLinkType($link->type),
                );
            }
            if (empty($outgoingLinks)) {
                $finalMilestone->follow($nodes[$issue->key], ScheduleLink::TYPE_SCHEDULE);
            }
            if ($issue->milestone) {
                $milestones[$issue->milestone]->follow($nodes[$issue->key], ScheduleLink::TYPE_SCHEDULE);
            }
        }

        if (!is_null($this->limitStrategy)) {
            $this->limitStrategy->apply($finalMilestone);
        }

        foreach ($milestones as $milestone) {
            $this->schedule->addMilestone($milestone);
        }

        return $this;
    }

    public function addMilestoneBuffer(): self
    {
        $milestone = $this->schedule->getFinalMilestone();
        $buffer = new MilestoneBuffer(
            "{$milestone->getName()}-buffer",
            (int)ceil($milestone->getLength(true) / 2),
            ['consumption' => 0],
        );
        $this->addBuffer($buffer, $milestone, $milestone->getPreceders());
        return $this;
    }

    public function addFeedingBuffers(): self
    {
        $criticalChain = Utils::getCriticalChain($this->schedule->getFinalMilestone());
        foreach ($criticalChain as $node) {
            $preceders = $node->getPreceders();
            $criticalPreceder = Utils::getLongestSequence($preceders);
            $notCriticalPreceders = array_filter($preceders, fn(Node $node) => $node !== $criticalPreceder);
            foreach ($notCriticalPreceders as $notCriticalPreceder) {
                $feedingChainLength = array_reduce(
                    array_filter(
                        $notCriticalPreceder->getPreceders(true),
                        fn(Node $node) => $node->getAttribute('state') !== ScheduleIssue::STATE_COMPLETED
                    ),
                    fn(int $acc, Node $node) => max($acc, $node->getDistance()),
                    $notCriticalPreceder->getDistance()
                )
                    - $notCriticalPreceder->getDistance()
                    + ($notCriticalPreceder->getAttribute('state') !== ScheduleIssue::STATE_COMPLETED ? $notCriticalPreceder->getLength() : 0);
                if ($feedingChainLength === 0) {
                    continue;
                }
                $buffer = new FeedingBuffer(
                    "{$notCriticalPreceder->getName()}-buffer",
                    (int)ceil($feedingChainLength / 2),
                    ['consumption' => 0],
                );
                $this->addBuffer($buffer, $node, [$notCriticalPreceder]);
            }
        }
        return $this;
    }

    public function addDates(): self
    {
        $finalMilestone = $this->schedule->getFinalMilestone();
        if (!is_null($this->scheduleStrategy)) {
            $this->scheduleStrategy->apply($finalMilestone);
        }
        $this->addBuffersDates(array_filter(
            $finalMilestone->getPreceders(true),
            fn(Node $node) => $node instanceof Buffer,
        ));
        foreach ($this->schedule->getMilestones() as $milestone) {
            $this->addMilestoneDates($milestone);
        }
        return $this;
    }

    public function addBuffersConsumption(): self
    {
        $milestone = $this->schedule->getFinalMilestone();

        $criticalChain = Utils::getCriticalChain($milestone);

        $milestoneBuffer = array_reduce(
            array_filter(
                $milestone->getPreceders(true),
                fn(Node $node) => $node instanceof MilestoneBuffer,
            ),
            fn($acc, Node $node) => $node
        );
        $lateDays = Utils::getLateDays(
            array_reduce($criticalChain, fn($acc, Node $node) => $node),
            $criticalChain,
            $this->context->now,
        );
        $milestoneBuffer->setAttribute('consumption', min($milestoneBuffer->getLength(), $lateDays));

        $feedingBuffers = array_filter(
            $milestone->getPreceders(true),
            fn(Node $node) => $node instanceof FeedingBuffer,
        );
        foreach ($feedingBuffers as $feedingBuffer) {
            $lateDays = max(array_map(
                fn(Node $tail) => Utils::getLateDays(
                    $tail,
                    array_udiff(
                        $feedingBuffer->getPreceders(true),
                        $criticalChain,
                        fn(Node $a, Node $b) => $a->getName() === $b->getName() ? 0 : ($a->getName() > $b->getName() ? 1 : -1),
                    ),
                    $this->context->now
                ),
                array_filter(
                    $feedingBuffer->getPreceders(true),
                    fn(Node $node) => !$node->hasPreceders(),
                ),
            ));
            $feedingBuffer->setAttribute('consumption', min($feedingBuffer->getLength(), $lateDays));
        }

        return $this;
    }

    protected function addBuffersDates(array $buffers): void
    {
        foreach ($buffers as $buffer) {
            $maxPrecederEndDate = new \DateTimeImmutable(array_reduce(
                $buffer->getPreceders(),
                fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
            ));
            $buffer->setAttribute('begin', $maxPrecederEndDate->format("Y-m-d"));
            $buffer->setAttribute('end', $maxPrecederEndDate->modify("{$buffer->getLength()} day")->format("Y-m-d"));
        }
    }

    protected function addMilestoneDates(Milestone $milestone): void
    {
        $milestoneEndDate = (new \DateTimeImmutable(array_reduce(
            $milestone->getPreceders(),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        )));
        $milestoneLength = Utils::getLongestSequence($milestone->getPreceders())->getLength(true);
        $milestone->setAttribute('begin', $milestoneEndDate->modify("-$milestoneLength day")->format("Y-m-d"));
        $milestone->setAttribute('end', $milestoneEndDate->format("Y-m-d"));
    }

    private function addBuffer(Buffer $buffer, Node $beforeNode, array $afterNodes): void
    {
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->unprecede($beforeNode, ScheduleLink::TYPE_SCHEDULE));
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->precede($buffer, ScheduleLink::TYPE_SCHEDULE));
        $buffer->precede($beforeNode, ScheduleLink::TYPE_SCHEDULE);
    }
}

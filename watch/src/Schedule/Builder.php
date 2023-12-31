<?php

namespace Watch\Schedule;

use Watch\Schedule;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Builder\ConvertStrategy;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;
use Watch\Subject\Model\Issue;

class Builder
{
    protected Milestone|null $milestone = null;

    /**
     * @param Context $context
     * @param Issue[] $issues
     * @param string[] $milestones
     * @param ConvertStrategy $convertStrategy
     * @param LimitStrategy|null $limitStrategy
     * @param ScheduleStrategy|null $scheduleStrategy
     */
    public function __construct(
        protected readonly Context $context,
        protected readonly array $issues,
        protected readonly array $milestones,
        private readonly ConvertStrategy $convertStrategy,
        private readonly LimitStrategy|null $limitStrategy = null,
        private readonly ScheduleStrategy|null $scheduleStrategy = null,
    )
    {
    }

    public function run(): self
    {
        $this->milestone = null;
        return $this;
    }

    public function release(): Schedule
    {
        return new Schedule([$this->milestone]);
    }

    public function addMilestone(): self
    {
        $nodes = array_reduce(
            array_map(
                fn(Issue $issue) => $this->convertStrategy->getTask($issue),
                $this->issues,
            ),
            fn(array $acc, Node $node) => [...$acc, $node->getName() => $node],
            []
        );

        $this->milestone = new Milestone(current($this->milestones));
        foreach ($this->issues as $issue) {
            $inwards = $issue->getInwardLinks();
            foreach ($inwards as $link) {
                $follower = $nodes[$link->key] ?? null;
                $preceder = $nodes[$issue->key] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder, $this->context->factory->getLink($link)->getType());
                }
            }
            if (empty($inwards)) {
                $node = $nodes[$issue->key] ?? null;
                if (!is_null($nodes)) {
                    $this->milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        if (!is_null($this->limitStrategy)) {
            $this->limitStrategy->apply($this->milestone);
        }

        return $this;
    }

    public function addMilestoneBuffer(): self
    {
        $buffer = new MilestoneBuffer(
            "{$this->milestone->getName()}-buffer",
            (int)ceil($this->milestone->getLength(true) / 2),
            ['consumption' => 0],
        );
        $this->addBuffer($buffer, $this->milestone, $this->milestone->getPreceders());
        return $this;
    }

    public function addFeedingBuffers(): self
    {
        $criticalChain = Utils::getCriticalChain($this->milestone);
        foreach ($criticalChain as $node) {
            $preceders = $node->getPreceders();
            $criticalPreceder = Utils::getLongestSequence($preceders);
            $notCriticalPreceders = array_filter($preceders, fn(Node $node) => $node !== $criticalPreceder);
            foreach ($notCriticalPreceders as $notCriticalPreceder) {
                $feedingChainLength = array_reduce(
                    array_filter(
                        $notCriticalPreceder->getPreceders(true),
                        fn(Node $node) => !$node->getAttribute('completed')
                    ),
                    fn(int $acc, Node $node) => max($acc, $node->getDistance()),
                    $notCriticalPreceder->getDistance()
                )
                    - $notCriticalPreceder->getDistance()
                    + !$notCriticalPreceder->getAttribute('completed') ?
                            $notCriticalPreceder->getLength() :
                            0;
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
        if (!is_null($this->scheduleStrategy)) {
            $this->scheduleStrategy->apply($this->milestone);
        }
        $this->addBuffersDates(array_filter(
            $this->milestone->getPreceders(true),
            fn(Node $node) => $node instanceof Buffer,
        ));
        $this->addMilestoneDates($this->milestone);
        return $this;
    }

    public function addBuffersConsumption(): self
    {
        $criticalChain = Utils::getCriticalChain($this->milestone);

        $milestoneBuffer = array_reduce(
            array_filter(
                $this->milestone->getPreceders(true),
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
            $this->milestone->getPreceders(true),
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
        $milestone->setAttribute('begin', $milestoneEndDate->modify("-{$milestoneLength} day")->format("Y-m-d"));
        $milestone->setAttribute('end', $milestoneEndDate->format("Y-m-d"));
    }

    private function addBuffer(Buffer $buffer, Node $beforeNode, array $afterNodes): void
    {
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->unprecede($beforeNode, Link::TYPE_SCHEDULE));
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->precede($buffer, Link::TYPE_SCHEDULE));
        $buffer->precede($beforeNode, Link::TYPE_SCHEDULE);
    }
}

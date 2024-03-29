<?php

namespace Watch\Schedule;

use Watch\Schedule;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Batch;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue as ScheduleIssue;
use Watch\Schedule\Model\Link as ScheduleLink;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Project;
use Watch\Schedule\Model\ProjectBuffer;
use Watch\Subject\Model\Issue as SubjectIssue;
use Watch\Subject\Model\Link as SubjectLink;

class Builder
{
    protected Schedule $schedule;

    /**
     * @param Context $context
     * @param SubjectIssue[] $issues
     * @param SubjectLink[] $links
     * @param string $project
     * @param string[] $milestones
     * @param Mapper $mapper
     * @param LimitStrategy|null $limitStrategy
     * @param ScheduleStrategy|null $scheduleStrategy
     */
    public function __construct(
        protected readonly Context $context,
        protected readonly array $issues,
        protected readonly array $links,
        protected readonly string $project,
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

    public function addProject(): self
    {
        $nodes = $this->getNodes($this->issues);

        foreach ($this->issues as $issue) {
            foreach (
                array_filter(
                    $this->links,
                    fn($link) => $link->from === $issue->key,
                ) as $link
            ) {
                $nodes[$link->to]->follow(
                    $nodes[$link->from],
                    $this->mapper->getLinkType($link->type),
                );
            }
        }

        $project = new Project($this->project);

        foreach ($this->issues as $issue) {
            if (empty(array_filter(
                $this->links,
                fn($link) => $link->from === $issue->key,
            ))) {
                $this->insertNode($nodes[$issue->key], $project, []);
            }
        }

        if (!is_null($this->limitStrategy)) {
            $this->limitStrategy->apply($project);
        }

        $this->schedule->setProject($project);

        return $this;
    }

    public function addMilestones(): self
    {
        $nodes = $this->schedule->getProject()->getLinkedNodes();

        /** @var SubjectIssue[] $issues */
        $issues = array_reduce(
            $this->issues,
            fn(array $acc, SubjectIssue $issue) => [
                ...$acc,
                $issue->key => $issue,
            ],
            [],
        );

        /** @var Milestone[] $milestones */
        $milestones = array_reduce(
            array_map(
                fn(string $milestone) => new Milestone($milestone),
                $this->milestones,
            ),
            fn(array $acc, Milestone $milestone) => [...$acc, $milestone->name => $milestone],
            []
        );

        foreach ($milestones as $milestone) {
            foreach (
                array_filter(
                    $this->issues,
                    fn($issue) => $issue->milestone === $milestone->name,
                ) as $issue
            ) {
                if (empty(array_filter(
                    $this->links,
                    fn($link) => $link->from === $issue->key && $issues[$link->to]->milestone === $issue->milestone,
                ))) {
                    $this->insertNode($nodes[$issue->key], $milestone, []);
                }
            }
        }

        return $this;
    }

    public function addProjectBuffer(): self
    {
        $project = $this->schedule->getProject();
        $this->insertNode(
            new ProjectBuffer(
                "{$project->name}-buf",
                (int)ceil($project->getLength(true) / 2),
                ['consumption' => 0],
            ),
            $project,
        );
        return $this;
    }

    public function addMilestoneBuffers(): self
    {
        foreach ($this->schedule->getProject()->getMilestones() as $milestone) {
            $this->insertNode(
                new MilestoneBuffer(
                    "{$milestone->name}-buf",
                    (int)ceil($milestone->getLength(true) / 2),
                    ['consumption' => 0],
                ),
                $milestone,
            );
        }
        return $this;
    }

    public function addFeedingBuffers(): self
    {
        foreach (Utils::getLongestChainNodes($this->schedule->getProject()) as $node) {
            $preceders = $node->getPreceders();
            $criticalPreceder = Utils::getMostDistantNode($preceders);
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
                    "{$notCriticalPreceder->name}-buf",
                    (int)ceil($feedingChainLength / 2),
                    ['consumption' => 0],
                );
                $this->insertNode($buffer, $node, [$notCriticalPreceder]);
            }
        }
        return $this;
    }

    public function addDates(): self
    {
        $project = $this->schedule->getProject();

        if (!is_null($this->scheduleStrategy)) {
            $this->scheduleStrategy->apply($project);
        }

        foreach (
            array_filter(
                $project->getLinkedNodes(),
                fn(Node $node) => $node instanceof Buffer,
            ) as $buffer
        ) {
            $this->addBufferDates($buffer);
        }

        foreach ([$project, ...$project->getMilestones()] as $batch) {
            $this->addBatchDates($batch);
        }

        return $this;
    }

    public function addBuffersConsumption(): self
    {
        $project = $this->schedule->getProject();

        $projectBuffer = $project->getBuffer();
        $projectBuffer->setAttribute('consumption', min(
            $projectBuffer->getLength(),
            Utils::getChainLateDays(Utils::getCriticalChain($project), $this->context->now),
        ));

        foreach ($project->getMilestones() as $milestone) {
            $buffer = $milestone->getBuffer();
            $buffer->setAttribute(
                'consumption',
                min(
                    $buffer->getLength(),
                    Utils::getChainLateDays(
                        Utils::getMilestoneChain($milestone),
                        $this->context->now,
                    ),
                ),
            );
        }

        $feedingChains = Utils::getFeedingChains($project);
        $feedingBuffers = array_filter(
            $project->getLinkedNodes(),
            fn(Node $node) => $node instanceof FeedingBuffer,
        );
        foreach ($feedingBuffers as $feedingBuffer) {
            $feedingBuffer->setAttribute(
                'consumption',
                min(
                    $feedingBuffer->getLength(),
                    Utils::getChainLateDays(
                        $feedingChains[$feedingBuffer->name],
                        $this->context->now,
                    ),
                ),
            );
        }

        return $this;
    }

    protected function addBufferDates(Buffer $buffer): void
    {
        $maxPrecederEndDate = new \DateTimeImmutable(array_reduce(
            $buffer->getPreceders(),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        ));
        $buffer
            ->setAttribute(
                'begin',
                $maxPrecederEndDate->format("Y-m-d")
            )
            ->setAttribute(
                'end',
                $maxPrecederEndDate->modify("{$buffer->getLength()} day")->format("Y-m-d")
            );
    }

    protected function addBatchDates(Batch $batch): void
    {
        $batchEndDate = (new \DateTimeImmutable(array_reduce(
            $batch->getPreceders(),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        )));
        $batchBeginDate = (new \DateTimeImmutable(array_reduce(
            $batch->getPreceders(true),
            fn($acc, Node $node) => min($acc, $node->getAttribute('begin')),
            $batchEndDate->format("Y-m-d"),
        )));
        $batch
            ->setAttribute('begin', $batchBeginDate->format("Y-m-d"))
            ->setAttribute('end', $batchEndDate->format("Y-m-d"));
    }

    private function insertNode(Node $node, Node $follower, array|null $preceders = null): void
    {
        foreach ($preceders ?? $follower->getPreceders() as $preceder) {
            $preceder->unprecede($follower, ScheduleLink::TYPE_SCHEDULE);
            $preceder->precede($node, ScheduleLink::TYPE_SCHEDULE);
        }
        $node->precede($follower, ScheduleLink::TYPE_SCHEDULE);
    }

    /**
     * @param $issues SubjectIssue[]
     * @return ScheduleIssue[]
     */
    private function getNodes(array $issues): array
    {
        return array_reduce(
            array_map(
                fn(SubjectIssue $issue) => new ScheduleIssue($issue->key, $issue->duration, [
                    'begin' => $issue->begin,
                    'end' => $issue->end,
                    'state' => $this->mapper->getIssueState($issue->status),
                ]),
                $issues,
            ),
            fn(array $acc, Node $node) => [...$acc, $node->name => $node],
            []
        );
    }
}

<?php

namespace Watch\Schedule;

use Watch\Schedule;
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
        $nodes = $this->getNodes($this->issues);

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

        foreach ($milestones as $milestone) {
            $this->schedule->getProject()->addMilestone($milestone);
        }

        return $this;
    }

    public function addProjectBuffer(): self
    {
        $project = $this->schedule->getProject();
        $this->insertNode(
            new ProjectBuffer(
                "{$project->getName()}-buffer",
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
                    "{$milestone->getName()}-buffer",
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
        $criticalChain = Utils::getCriticalChain($this->schedule->getProject());
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

        foreach ([$project, ...$project->getMilestones()] as $milestone) {
            foreach(
                array_filter(
                    $milestone->getPreceders(true),
                    fn(Node $node) => $node instanceof Buffer,
                ) as $buffer
            ) {
                $this->addBufferDates($buffer);
            }
        }

        foreach ([$project, ...$project->getMilestones()] as $milestone) {
            $this->addMilestoneDates($milestone);
        }
        return $this;
    }

    public function addBuffersConsumption(): self
    {
        $project = $this->schedule->getProject();

        $criticalChain = Utils::getCriticalChain($project);

        $projectBuffer = array_reduce(
            array_filter(
                $project->getPreceders(true),
                fn(Node $node) => $node instanceof ProjectBuffer,
            ),
            fn($acc, Node $node) => $node
        );
        $lateDays = Utils::getLateDays(
            array_reduce($criticalChain, fn($acc, Node $node) => $node),
            $criticalChain,
            $this->context->now,
        );
        $projectBuffer->setAttribute('consumption', min($projectBuffer->getLength(), $lateDays));

        $feedingBuffers = array_filter(
            $project->getPreceders(true),
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

    protected function addBufferDates(Buffer $buffer): void
    {
        $maxPrecederEndDate = new \DateTimeImmutable(array_reduce(
            $buffer->getPreceders(),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        ));
        $buffer->setAttribute('begin', $maxPrecederEndDate->format("Y-m-d"));
        $buffer->setAttribute('end', $maxPrecederEndDate->modify("{$buffer->getLength()} day")->format("Y-m-d"));
    }

    protected function addMilestoneDates(Milestone|Project $milestone): void
    {
        $milestoneEndDate = (new \DateTimeImmutable(array_reduce(
            $milestone->getPreceders(),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        )));
        $milestoneBeginDate = (new \DateTimeImmutable(array_reduce(
            $milestone->getPreceders(true),
            fn($acc, Node $node) => min($acc, $node->getAttribute('begin')),
            $milestoneEndDate->format("Y-m-d"),
        )));
        $milestone->setAttribute('begin', $milestoneBeginDate->format("Y-m-d"));
        $milestone->setAttribute('end', $milestoneEndDate->format("Y-m-d"));
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

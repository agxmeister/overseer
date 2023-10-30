<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;

abstract class Builder
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_BUFFERS = 'buffers';
    const VOLUME_MILESTONES = 'milestones';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    protected Milestone|null $milestone;

    public function __construct(protected readonly array $issues, protected readonly \DateTimeImmutable $now)
    {
    }

    public function run(): self
    {
        $this->milestone = null;
        return $this;
    }

    public function release(): array
    {
        return [
            self::VOLUME_ISSUES => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter($this->milestone->getPreceders(true), fn(Node $node) => $node instanceof Issue)
            )),
            self::VOLUME_BUFFERS => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                    'consumption' => $node->getAttribute('consumption'),
                ],
                array_filter($this->milestone->getPreceders(true), fn(Node $node) => $node instanceof Buffer)
            )),
            self::VOLUME_MILESTONES => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter([$this->milestone, ...$this->milestone->getPreceders(true)], fn(Node $node) => $node instanceof Milestone)
            )),
            self::VOLUME_LINKS => \Watch\Utils::getUnique(
                array_reduce(
                    $this->milestone->getPreceders(true),
                    fn($acc, Node $node) => [
                        ...$acc,
                        ...array_map(fn(Link $link) => [
                            'from' => $node->getName(),
                            'to' => $link->getNode()->getName(),
                            'type' => $link->getType(),
                        ], $node->getFollowLinks()),
                        ...array_map(fn(Link $link) => [
                            'from' => $link->getNode()->getName(),
                            'to' => $node->getName(),
                            'type' => $link->getType(),
                        ], $node->getPrecedeLinks()),
                    ],
                    []
                ),
                fn($link) => implode('-', array_values($link))
            ),
            self::VOLUME_CRITICAL_CHAIN => array_reduce(
                array_filter(Utils::getCriticalChain($this->milestone), fn(Node $node) => !($node instanceof Buffer)),
                fn($acc, Node $node) => [...$acc, $node->getName()],
                []
            ),
        ];
    }

    public function addMilestone(): self
    {
        $this->milestone = Utils::getMilestone($this->issues);
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
                $buffer = new FeedingBuffer(
                    "{$notCriticalPreceder->getName()}-buffer",
                    (int)ceil($notCriticalPreceder->getLength(true) / 2),
                    ['consumption' => 0],
                );
                $this->addBuffer($buffer, $node, [$notCriticalPreceder]);
            }
        }
        return $this;
    }

    public function addDates(): self
    {
        Utils::setDates($this->milestone);
        return $this;
    }

    public function addBuffersConsumption(): self
    {
        $lateDays = array_reduce(
            array_filter(
                array_filter(
                    Utils::getCriticalChain($this->milestone),
                    fn(Node $node) => $node instanceof Issue
                ),
                fn(Node $node) =>
                    $node->getAttribute('end') < $this->now->format('Y-m-d') &&
                    !$node->getAttribute('isCompleted')
            ),
            fn(int $acc, Node $node) => $acc + (int)$this->now->diff(new \DateTimeImmutable($node->getAttribute('end')))->format('%a') - 1,
            0,
        );

        $milestoneBuffer = array_reduce(
            array_filter(
                Utils::getCriticalChain($this->milestone),
                fn(Node $node) => $node instanceof Buffer
            ),
            fn($acc, Node $node) => $node
        );

        $milestoneBuffer->setAttribute('consumption', $lateDays);

        return $this;
    }

    private function addBuffer(Buffer $buffer, Node $beforeNode, array $afterNodes): void
    {
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->unprecede($beforeNode, Link::TYPE_SCHEDULE));
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->precede($buffer, Link::TYPE_SCHEDULE));
        $buffer->precede($beforeNode, Link::TYPE_SCHEDULE);
    }
}

<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

trait AbleToBuild
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_BUFFERS = 'buffers';
    const VOLUME_MILESTONES = 'milestones';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    protected Milestone|null $milestone;

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
                        fn(Node $node) => !$node->getAttribute('isCompleted')
                    ),
                    fn(int $acc, Node $node) => max($acc, $node->getDistance()),
                    $notCriticalPreceder->getDistance()
                )
                    - $notCriticalPreceder->getDistance()
                    + !$notCriticalPreceder->getAttribute('isCompleted') ?
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
            $this->context->getNow(),
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
                    $this->context->getNow()
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
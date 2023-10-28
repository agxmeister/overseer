<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;

abstract class Builder
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';
    const VOLUME_BUFFERS = 'buffers';

    protected Milestone|null $milestone;

    protected array|null $result;

    public function __construct(protected readonly array $issues)
    {
    }

    public function run(): self
    {
        $this->milestone = null;
        $this->result = [
            self::VOLUME_ISSUES => [],
            self::VOLUME_CRITICAL_CHAIN => [],
            self::VOLUME_BUFFERS => [],
        ];
        return $this;
    }

    public function release(): array
    {
        $this->dumpIssues();
        $this->dumpBuffers();
        $this->dumpLinks();
        $this->dumpCriticalChain();
        return array_map(fn($values) => array_values($values), $this->result);
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

    private function addBuffer(Buffer $buffer, Node $beforeNode, array $afterNodes): void
    {
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->unprecede($beforeNode, Link::TYPE_SCHEDULE));
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->precede($buffer, Link::TYPE_SCHEDULE));
        $buffer->precede($beforeNode, Link::TYPE_SCHEDULE);
    }

    private function dumpIssues(): void
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_map(
            fn(Node $node) => [
                'key' => $node->getName(),
                'begin' => $node->getAttribute('begin'),
                'end' => $node->getAttribute('end'),
            ],
            array_filter($this->milestone->getPreceders(true), fn(Node $node) => get_class($node) === Node::class)
        ));
    }

    private function dumpBuffers(): void
    {
        $this->applyDiffToResult(self::VOLUME_BUFFERS, array_map(
            fn(Node $node) => [
                'key' => $node->getName(),
                'begin' => $node->getAttribute('begin'),
                'end' => $node->getAttribute('end'),
            ],
            array_filter($this->milestone->getPreceders(true), fn(Node $node) => $node instanceof Buffer)
        ));
    }

    private function dumpLinks(): void
    {
        $this->result[self::VOLUME_LINKS] = \Watch\Utils::getUnique(
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
        );
    }

    private function dumpCriticalChain(): void
    {
        $this->result[self::VOLUME_CRITICAL_CHAIN] = array_reduce(
            array_filter(Utils::getCriticalChain($this->milestone), fn(Node $node) => !($node instanceof Buffer)),
            fn($acc, Node $node) => [...$acc, $node->getName()],
            []
        );
    }

    private function applyDiffToResult(string $volume, array $diff): void
    {
        foreach ($diff as $item) {
            if (!isset($item['key'])) {
                continue;
            }
            $this->addToResult($volume, $item['key'], $item);
        }
    }

    private function addToResult(string $volume, string $key, array $values): void
    {
        $this->result[$volume][$key] = [
            ...($this->result[$volume][$key] ?? ['key' => $key]),
            ...array_filter($values, fn($key) => $key !== 'key', ARRAY_FILTER_USE_KEY)
        ];
    }

}

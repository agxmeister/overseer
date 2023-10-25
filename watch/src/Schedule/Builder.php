<?php

namespace Watch\Schedule;

use DateTime;
use Watch\Schedule\Strategy\Limit\Strategy as LimitStrategy;
use Watch\Schedule\Strategy\Schedule\Strategy as ScheduleStrategy;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;

class Builder
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';
    const VOLUME_BUFFERS = 'buffers';

    private Milestone|null $milestone = null;

    private array|null $buffers = null;

    private array|null $result = null;

    public function run(array $issues): self
    {
        $this->milestone = Utils::getMilestone($issues);
        $this->buffers = [];
        $this->result = [
            self::VOLUME_ISSUES => [],
            self::VOLUME_CRITICAL_CHAIN => [],
            self::VOLUME_BUFFERS => [],
        ];
        return $this;
    }

    public function setLimit(LimitStrategy $strategy): self
    {
        $strategy->apply($this->milestone);
        return $this;
    }

    public function setSchedule(ScheduleStrategy $strategy): self
    {
        $strategy->apply($this->milestone);
        return $this;
    }

    public function addIssuesDates(): self
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_map(
            fn(Node $node) => [
                'key' => $node->getName(),
                'begin' => $node->getAttribute('begin'),
                'end' => $node->getAttribute('end'),
            ],
            array_filter($this->milestone->getPreceders(true), fn(Node $node) => get_class($node) === Node::class)
        ));
        return $this;
    }

    public function addBuffersDates(): self
    {
        $this->applyDiffToResult(self::VOLUME_BUFFERS, array_filter(array_map(function (Buffer $buffer) {
            $end = max(array_map(
                fn(Node $node) => $this->getResult(self::VOLUME_ISSUES, $node->getName())['end'] ?? null,
                $buffer->getPreceders()
            ));
            $date = new DateTime($end);
            $startDate = (clone $date)->modify("1 day");
            $finishDate = (clone $date)->modify("{$buffer->getLength()} day");
            return [
                'key' => $buffer->getName(),
                'begin' => $startDate->format("Y-m-d"),
                'end' => $finishDate->format("Y-m-d"),
            ];
        }, $this->buffers), fn($item) => !is_null($item)));
        return $this;
    }

    public function addLinks():self
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
        return $this;
    }

    public function addCriticalChain(): self
    {
        $this->result[self::VOLUME_CRITICAL_CHAIN] = array_reduce(
            Utils::getCriticalChain($this->milestone),
            fn($acc, Node $node) => [...$acc, $node->getName()],
            []
        );
        return $this;
    }

    public function addMilestoneBuffer(): self
    {
        $this->buffers[] = $this->addBuffer(
            "{$this->milestone->getName()}-buffer",
            (int)ceil($this->milestone->getLength(true) / 2),
            $this->milestone,
            $this->milestone->getPreceders(),
        );
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
                $this->buffers[] = $this->addBuffer(
                    "{$notCriticalPreceder->getName()}-buffer",
                    (int)ceil($notCriticalPreceder->getLength(true) / 2),
                    $node,
                    [$notCriticalPreceder],
                );
            }
        }
        return $this;
    }

    public function release(): array
    {
        return array_map(fn($values) => array_values($values), $this->result);
    }

    private function getNode($key): Node|null
    {
        return array_reduce(
            $this->milestone->getPreceders(true),
            fn($acc, Node $node) => $node->getName() === $key ? $node : $acc
        );
    }

    private function addBuffer(string $name, int $length, Node $beforeNode, array $afterNodes): Buffer
    {
        $buffer = new Buffer($name, $length);
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->unprecede($beforeNode, Link::TYPE_SCHEDULE));
        array_walk($afterNodes, fn(Node $afterNode) => $afterNode->precede($buffer, Link::TYPE_SCHEDULE));
        $buffer->precede($beforeNode, Link::TYPE_SCHEDULE);
        return $buffer;
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

    private function getIssue($key): array|null
    {
        return array_reduce($this->issues, fn($acc, $issue) => $issue['key'] === $key ? $issue : $acc);
    }

    private function getResult($volume, $key): array|null
    {
        return $this->result[$volume][$key] ?? null;
    }
}

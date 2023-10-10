<?php

namespace Watch\Schedule;

use DateTime;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Strategy\Strategy;

class Builder
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';
    const VOLUME_BUFFERS = 'buffers';

    private array|null $issues = null;
    private Milestone|null $milestone = null;

    private array|null $buffers = null;

    private array|null $result = null;

    public function run(array $issues): self
    {
        $this->issues = $issues;
        $this->milestone = Utils::getMilestone($issues);
        $this->buffers = [];
        $this->result = [
            self::VOLUME_ISSUES => [],
            self::VOLUME_CRITICAL_CHAIN => [],
            self::VOLUME_BUFFERS => [],
        ];
        return $this;
    }

    public function distribute(Strategy $strategy): self
    {
        $strategy->schedule($this->milestone);
        return $this;
    }

    public function schedule(DateTime $date): self
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_filter(array_map(function (array $issue) use ($date) {
            /** @var Node $node */
            $node = $this->getNode($issue['key']);
            if (is_null($node)) {
                return null;
            }
            $distance = $node->getDistance();
            $completion = $node->getCompletion();
            $startDate = (clone $date)->modify("-{$distance} day");
            $finishDate = (clone $date)->modify("-{$completion} day");
            return [
                'key' => $issue['key'],
                'begin' => $startDate->format("Y-m-d"),
                'end' => $finishDate->format("Y-m-d"),
            ];
        }, $this->issues), fn($item) => !is_null($item)));
        return $this;
    }

    public function addIssuesDates(): self
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_filter(array_map(function (array $issue) {
            /** @var Node $node */
            $node = $this->getNode($issue['key']);
            if (is_null($node)) {
                return null;
            }
            return [
                'key' => $issue['key'],
                'begin' => $issue['begin'],
                'end' => $issue['end'],
            ];
        }, $this->issues), fn($item) => !is_null($item)));
        return $this;
    }

    public function addBuffersDates(): self
    {
        $this->applyDiffToResult(self::VOLUME_BUFFERS, array_filter(array_map(function (Buffer $buffer) {
            $end = max(...array_map(
                fn(Node $node) => $this->getIssue($node->getName())['end'] ?? null,
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

    public function addIssuesLinks(): self
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_filter(array_map(function (array $issue) {
            /** @var Node $node */
            $node = $this->getNode($issue['key']);
            if (is_null($node)) {
                return null;
            }
            $links = $this->getLinks($node);
            if (empty($links)) {
                return null;
            }
            return [
                'key' => $issue['key'],
                'links' => $links,
            ];
        }, $this->issues), fn($item) => !is_null($item)));
        return $this;
    }

    public function addBuffersLinks(): self
    {
        $this->applyDiffToResult(self::VOLUME_BUFFERS, array_filter(array_map(function (Buffer $buffer) {
            $links = $this->getLinks($buffer);
            if (empty($links)) {
                return null;
            }
            return [
                'key' => $buffer->getName(),
                'links' => $links,
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
        $criticalChain = Utils::getCriticalChain($this->milestone);
        $criticalChainNodes = [$criticalChain, ...$criticalChain->getPreceders(true)];
        $this->result[self::VOLUME_CRITICAL_CHAIN] = array_reduce(
            $criticalChainNodes,
            fn($acc, Node $node) => [...$acc, $node->getName()],
            []
        );
        return $this;
    }

    public function addMilestoneBuffer(): self
    {
        $criticalChain = Utils::getCriticalChain($this->milestone);
        $buffer = new Buffer("{$this->milestone->getName()}-buffer", $criticalChain->getLength(true) / 2);
        $nodes = $this->milestone->getPreceders();
        array_walk($nodes, fn(Node $node) => $node->unprecede($this->milestone));
        array_walk($nodes, fn(Node $node) => $node->precede($buffer, Link::TYPE_SCHEDULE));
        $buffer->precede($this->milestone, Link::TYPE_SCHEDULE);
        $this->buffers[] = $buffer;
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

    private function getLinks(Node $node): array
    {
        return array_filter([
            'inward' => array_values(array_map(
                fn(Link $link) => [
                    'key' => $link->getNode()->getName(),
                    'type' => $link->getType(),
                ],
                array_filter(
                    $node->getFollowLinks(),
                    fn(Link $link) => !is_a($link->getNode(), Milestone::class) && !is_a($link->getNode(), Buffer::class)
                )
            )),
            'outward' => array_values(array_map(
                fn(Link $link) => [
                    'key' => $link->getNode()->getName(),
                    'type' => $link->getType(),
                ],
                array_filter(
                    $node->getPrecedeLinks(),
                    fn(Link $link) => !is_a($link->getNode(), Milestone::class) && !is_a($link->getNode(), Buffer::class)
                )
            )),
        ], fn($links) => !empty($links));
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
}

<?php

namespace Watch\Schedule;

use DateTime;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Strategy\Strategy;

class Builder
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    private array|null $issues = null;
    private Milestone|null $milestone = null;

    private array|null $result = null;

    public function run(array $issues): self
    {
        $this->issues = $issues;
        $this->milestone = Utils::getMilestone($issues);
        $this->result = [
            self::VOLUME_ISSUES => [],
            self::VOLUME_CRITICAL_CHAIN => [],
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

    public function addDates(): self
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

    public function addLinks(): self
    {
        $this->applyDiffToResult(self::VOLUME_ISSUES, array_filter(array_map(function (array $issue) {
            /** @var Node $node */
            $node = $this->getNode($issue['key']);
            if (is_null($node)) {
                return null;
            }

            $inwardLinks = array_values(array_map(
                fn(Link $link) => [
                    'key' => $link->getNode()->getName(),
                    'type' => $link->getType(),
                ],
                array_filter(
                    $node->getFollowLinks(),
                    fn(Link $link) => !is_a($link->getNode(), Milestone::class)
                )
            ));

            $outwardLinks = array_values(array_map(
                fn(Link $link) => [
                    'key' => $link->getNode()->getName(),
                    'type' => $link->getType(),
                ],
                array_filter(
                    $node->getPrecedeLinks(),
                    fn(Link $link) => !is_a($link->getNode(), Milestone::class)
                )
            ));

            $links = [];
            if ($inwardLinks) {
                $links['inward'] = $inwardLinks;
            }
            if ($outwardLinks) {
                $links['outward'] = $outwardLinks;
            }
            if (!$links) {
                return null;
            }
            return [
                'key' => $issue['key'],
                'links' => $links,
            ];
        }, $this->issues), fn($item) => !is_null($item)));
        return $this;
    }

    public function addCriticalChain(): self
    {
        $nodes = $this->milestone->getPreceders(true);
        $longestNode = Utils::getLongestSequence($nodes);
        $criticalChainNodes = [$longestNode, ...$longestNode->getPreceders(true)];
        $this->result[self::VOLUME_CRITICAL_CHAIN] = array_reduce(
            $criticalChainNodes,
            fn($acc, Node $node) => [...$acc, $node->getName()],
            []
        );
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

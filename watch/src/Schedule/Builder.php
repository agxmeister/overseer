<?php

namespace Watch\Schedule;

use DateTime;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Strategy\Strategy;

class Builder
{
    private array|null $issues = null;
    private Milestone|null $milestone = null;

    private array|null $result = null;

    public function run(array $issues): self
    {
        $this->issues = $issues;
        $this->milestone = Utils::getMilestone($issues);
        $this->result = [];
        return $this;
    }

    public function distribute(Strategy $strategy): self
    {
        $strategy->schedule($this->milestone);
        return $this;
    }

    public function schedule(DateTime $date): self
    {
        $this->applyDiffToResult(array_filter(array_map(function (array $issue) use ($date) {
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
                'estimatedBeginDate' => $startDate->format("Y-m-d"),
                'estimatedEndDate' => $finishDate->format("Y-m-d"),
            ];
        }, $this->issues), fn($item) => !is_null($item)));
        return $this;
    }

    public function addLinks(): self
    {
        $this->applyDiffToResult(array_filter(array_map(function (array $issue) {
            /** @var Node $node */
            $node = $this->getNode($issue['key']);
            if (is_null($node)) {
                return null;
            }
            $inwardLinks = array_values(array_map(
                fn(Node $follower) => [
                    'key' => $follower->getName(),
                    'type' => 'Follows',
                ],
                array_filter(
                    $node->getFollowers([Link::TYPE_SCHEDULE]),
                    fn(Node $node) => !is_a($node, Milestone::class)
                )
            ));
            $outwardLinks = array_values(array_map(
                fn(Node $preceder) => [
                    'key' => $preceder->getName(),
                    'type' => 'Follows',
                ],
                array_filter(
                    $node->getPreceders(false, [Link::TYPE_SCHEDULE]),
                    fn(Node $node) => !is_a($node, Milestone::class)
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
        foreach ($criticalChainNodes as $criticalChainNode) {
            $this->addToResult($criticalChainNode->getName(), [
                'isCritical' => true,
            ]);
        }
        return $this;
    }

    public function release(): array
    {
        return array_values($this->result);
    }

    private function getNode($key): Node|null
    {
        return array_reduce(
            $this->milestone->getPreceders(true),
            fn($acc, Node $node) => $node->getName() === $key ? $node : $acc
        );
    }

    private function applyDiffToResult(array $diff): void
    {
        foreach ($diff as $item) {
            if (!isset($item['key'])) {
                continue;
            }
            $this->addToResult($item['key'], $item);
        }
    }

    private function addToResult(string $key, array $values): void
    {
        $this->result[$key] = [
            ...($this->result[$key] ?? ['key' => $key]),
            ...array_filter($values, fn($key) => $key !== 'key', ARRAY_FILTER_USE_KEY)
        ];
    }
}

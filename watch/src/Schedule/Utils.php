<?php

namespace Watch\Schedule;

use Watch\Issue;
use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Task;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;

class Utils
{
    /**
     * @param Node|null $node
     * @return Node[]
     */
    static public function getCriticalChain(Node|null $node): array
    {
        if (is_null($node)) {
            return [];
        }
        return [$node, ...self::getCriticalChain(Utils::getLongestSequence(
            array_filter(
                array_filter(
                    $node->getPreceders(),
                    fn($node) => get_class($node) !== FeedingBuffer::class,
                ),
                fn(Node $node) => !array_reduce(
                    $node->getFollowers(),
                    fn($acc, Node $node) => $acc || get_class($node) === FeedingBuffer::class,
                    false,
                ),
            )
        ))];
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getLongestSequence(array $nodes, array $types = []): Node|null
    {
        if (sizeof($nodes) === 0) {
            return null;
        }
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) < $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getShortestSequence(array $nodes, array $types = []): Node|null
    {
        if (sizeof($nodes) === 0) {
            return null;
        }
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) > $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    /**
     * @param Issue[] $issues
     * @param LimitStrategy|null $strategy
     * @return Milestone
     */
    static public function getMilestone(array $issues, LimitStrategy $strategy = null): Milestone
    {
        $nodes = [];
        foreach ($issues as $issue) {
            $node = new Task($issue->key, $issue->duration, [
                'begin' => $issue->begin,
                'end' => $issue->end,
                'isStarted' => $issue->isStarted,
                'isCompleted' => $issue->isCompleted,
            ]);
            $nodes[$node->getName()] = $node;
        }

        $milestone = new Milestone('finish');
        foreach ($issues as $issue) {
            $inwards = $issue->links['inward'];
            foreach ($inwards as $link) {
                $follower = $nodes[$link['key']] ?? null;
                $preceder = $nodes[$issue->key] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder, $link['type']);
                }
            }
            if (empty($inwards)) {
                $node = $nodes[$issue->key] ?? null;
                if (!is_null($nodes)) {
                    $milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        if (!is_null($strategy)) {
            $strategy->apply($milestone);
        }

        return $milestone;
    }

    static public function getLateDays(Node $node, array $filter, \DateTimeImmutable $now): int
    {
        $nodeLateDays = (
            !$node->getAttribute('isCompleted') &&
            $node->getAttribute('end') <= $now->format('Y-m-d')
        )
            ? $now->diff(new \DateTimeImmutable($node->getAttribute('end')))->format('%a')
            : 0;
        return max([$nodeLateDays, ...array_map(
            fn(int $followerLateDays) => $followerLateDays + $nodeLateDays,
            array_map(
                fn(Node $follower) => self::getLateDays($follower, $filter, $now),
                array_uintersect(
                    $node->getFollowers(),
                    $filter,
                    fn(Node $a, Node $b) => $a->getName() === $b->getName() ? 0 : ($a->getName() > $b->getName() ? 1 : -1),
                ),
            )
        )]);
    }
}

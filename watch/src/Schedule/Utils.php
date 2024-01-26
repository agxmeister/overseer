<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;

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

    static public function getLateDays(Node $node, array $filter, \DateTimeImmutable $now): int
    {
        $nodeLateDays = (
            $node->getAttribute('state') !== Issue::STATE_COMPLETED &&
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

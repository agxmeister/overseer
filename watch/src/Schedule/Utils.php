<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;

class Utils
{
    static public function getDuplicate(Node $origin): Node
    {
        return self::getDuplicateRecursively($origin);
    }

    static public function getCriticalChain(Node $origin): Node
    {
        $copy = self::getDuplicate($origin);
        foreach (
            array_filter(
                $copy->getPreceders(true),
                fn(Node $preceder) => $preceder instanceof FeedingBuffer,
            ) as $feedingBuffer) {
            foreach ($feedingBuffer->getFollowLinks() as $link) {
                $feedingBuffer->unprecede($link->node);
            }
        }
        return $copy;
    }

    /**
     * @param Node $origin
     * @return Node[]
     */
    static public function getFeedingChains(Node $origin): array
    {
        /** @var Node[] $nodes */
        $nodes = array_reduce(
            self::getDuplicate($origin)->getPreceders(true),
            fn($acc, Node $node) => [...$acc, $node->name => $node],
            []
        );

        foreach (self::getCriticalChain($origin)->getPreceders(true) as $node) {
            $nodes[$node->name]->unlink();
        }

        return array_reduce(
            array_filter(
                $nodes,
                fn(Node $node) => $node instanceof FeedingBuffer,
            ),
            fn($acc, Node $feedingBuffer) => [...$acc, ...$feedingBuffer->getPreceders()],
            [],
        );
    }

    /**
     * @param Node|null $node
     * @return Node[]
     */
    static public function getLongestChain(Node|null $node): array
    {
        if (is_null($node)) {
            return [];
        }
        return [
            $node,
            ...self::getLongestChain(Utils::getMostDistantNode(
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
            ))
        ];
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getMostDistantNode(array $nodes, array $types = []): Node|null
    {
        $node = reset($nodes);
        if ($node === false) {
            return null;
        }
        return array_reduce(
            $nodes,
            fn(Node $acc, Node $node) =>
                $acc->getDistance(true, $types) < $node->getDistance(true, $types)
                    ? $node
                    : $acc,
            $node,
        );
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getLeastDistantNode(array $nodes, array $types = []): Node|null
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

    static public function getChainLateDays(array $chain, \DateTimeImmutable $now): int
    {
        return array_reduce(
            $chain,
            fn(int $acc, Node $node) => $acc + self::getNodeLateDays($node, $now),
            0,
        );
    }

    static public function getNodeLateDays(Node $node, \DateTimeImmutable $now): int
    {
        return (
            $node->getAttribute('state') !== Issue::STATE_COMPLETED &&
            $node->getAttribute('end') <= $now->format('Y-m-d')
        )
            ? $now->diff(new \DateTimeImmutable($node->getAttribute('end')))->format('%a')
            : 0;
    }

    static public function getLateDays(Node $node, array $filter, \DateTimeImmutable $now): int
    {
        $nodeLateDays = self::getNodeLateDays($node, $now);
        return max([$nodeLateDays, ...array_map(
            fn(int $followerLateDays) => $followerLateDays + $nodeLateDays,
            array_map(
                fn(Node $follower) => self::getLateDays($follower, $filter, $now),
                array_uintersect(
                    $node->getFollowers(),
                    $filter,
                    fn(Node $a, Node $b) => (int)($a->name === $b->name),
                ),
            )
        )]);
    }

    static private function getDuplicateRecursively(Node $origin, &$copies = []): Node
    {
        if (!isset($copies[$origin->name])) {
            $copies[$origin->name] = clone $origin;
        }
        $copy = $copies[$origin->name];
        foreach ($origin->getPrecedeLinks() as $link) {
            $precederCopy = self::getDuplicateRecursively($link->node, $copies);
            $precederCopy->precede($copy, $link->type);
        }
        return $copy;
    }
}

<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Chain;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Project;

class Utils
{
    static public function getDuplicate(Node $origin): Node
    {
        return self::getDuplicateRecursively($origin);
    }

    static public function getCriticalChain(Project $origin): Chain
    {
        /** @var Project $copy */
        $copy = self::getDuplicate($origin);
        foreach (
            array_filter(
                $copy->getNodes(),
                fn(Node $node) => $node instanceof FeedingBuffer,
            ) as $feedingBuffer) {
            foreach ($feedingBuffer->getFollowLinks() as $link) {
                $feedingBuffer->unprecede($link->node);
            }
        }
        $originNodes = $origin->getNodes();
        return new Chain(array_map(
            fn(Node $node) => $originNodes[$node->name],
            array_slice(self::getLongestChainNodes($copy->getBuffer()), 1),
        ));
    }

    static public function getMilestoneChain(Milestone $milestone): Chain
    {
        return new Chain(array_slice(self::getLongestChainNodes($milestone->getBuffer()), 1));
    }

    /**
     * @param Project $origin
     * @return Chain[]
     */
    static public function getFeedingChains(Project $origin): array
    {
        /** @var Node[] $nodes */
        $nodes = array_reduce(
            self::getDuplicate($origin)->getPreceders(true),
            fn($acc, Node $node) => [...$acc, $node->name => $node],
            []
        );

        foreach (self::getCriticalChain($origin)->nodes as $node) {
            $nodes[$node->name]->unlink();
        }

        $originNodes = $origin->getNodes();
        return array_reduce(
            array_filter(
                $nodes,
                fn(Node $node) => $node instanceof FeedingBuffer,
            ),
            fn($acc, Node $feedingBuffer) => [
                ...$acc,
                $feedingBuffer->name => new Chain(array_map(
                    fn(Node $node) => $originNodes[$node->name],
                    array_slice(self::getLongestChainNodes($feedingBuffer), 1),
                )),
            ],
            [],
        );
    }

    /**
     * @param Node $node
     * @return Node[]
     */
    static public function getLongestChainNodes(Node $node): array
    {
        $preceders = $node->getPreceders();
        if (empty($preceders)) {
            return [$node];
        }

        $lengths = self::getLengthsPerNodeName($preceders);
        $nodes = self::getNodesPerNodeName($preceders);

        arsort($lengths);
        $longestNodeName = array_key_first($lengths);

        return [
            $node,
            ...self::getLongestChainNodes($nodes[$longestNodeName]),
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

    static public function getChainLateDays(Chain $chain, \DateTimeImmutable $now): int
    {
        return array_reduce(
            $chain->nodes,
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

    static private function getDuplicateRecursively(Node $origin, &$copies = []): Node
    {
        if (isset($copies[$origin->name])) {
            return $copies[$origin->name];
        }
        $copies[$origin->name] = clone $origin;
        foreach ($origin->getPrecedeLinks() as $link) {
            $precederCopy = self::getDuplicateRecursively($link->node, $copies);
            $precederCopy->precede($copies[$origin->name], $link->type);
        }
        foreach ($origin->getFollowLinks() as $link) {
            $followerCopy = self::getDuplicateRecursively($link->node, $copies);
            $followerCopy->follow($copies[$origin->name], $link->type);
        }
        return $copies[$origin->name];
    }

    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    static private function getNodesPerNodeName(array $nodes): array
    {
        return array_reduce(
            $nodes,
            fn($acc, Node $node) => [
                ...$acc,
                $node->name => $node,
            ],
            [],
        );
    }

    /**
     * @param Node[] $nodes
     * @return int[]
     */
    static private function getLengthsPerNodeName(array $nodes): array
    {
        return array_reduce(
            $nodes,
            fn($acc, Node $node) => [
                ...$acc,
                $node->name => $node->getLength(true),
            ],
            [],
        );
    }
}

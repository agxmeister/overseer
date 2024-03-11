<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Chain;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\MilestoneBuffer;
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
        return self::getChain($copy->getBuffer(), false);
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

        return array_reduce(
            array_filter(
                $nodes,
                fn(Node $node) => $node instanceof FeedingBuffer,
            ),
            fn($acc, Node $feedingBuffer) => [
                ...$acc,
                $feedingBuffer->name => self::getChain($feedingBuffer, false),
            ],
            [],
        );
    }

    /**
     * @param Project $origin
     * @return Chain[]
     */
    static public function getMilestoneChains(Project $origin): array
    {
        /** @var Project $copy */
        $copy = self::getDuplicate($origin);
        return array_reduce(
            array_filter(
                $copy->getNodes(),
                fn(Node $node) => $node instanceof MilestoneBuffer,
            ),
            fn($acc, Node $milestoneBuffer) => [
                ...$acc,
                $milestoneBuffer->name => self::getChain($milestoneBuffer),
            ],
            [],
        );
    }

    static public function cropBranches(Node $node): void
    {
        $preceders = $node->getPreceders();
        if (empty($preceders)) {
            return;
        }

        $lengthsPerNodeName = self::getLengthsPerNodeName($preceders);
        arsort($lengthsPerNodeName);
        $longestNodeName = array_key_first($lengthsPerNodeName);

        foreach ($preceders as $preceder) {
            if ($preceder->name !== $longestNodeName) {
                $preceder->unprecede($node);
                continue;
            }
            self::cropBranches($preceder);
        }
    }

    static public function getChain(Node $node, bool $includeLeadingNode = true): Chain
    {
        $chainNodes = [];
        self::getChainNodes($node, $chainNodes);
        return new Chain($includeLeadingNode ? $chainNodes : array_slice($chainNodes, 1));
    }

    /**
     * @param Node|null $node
     * @return Node[]
     */
    static public function getLongestPath(Node|null $node): array
    {
        if (is_null($node)) {
            return [];
        }
        return [
            $node,
            ...self::getLongestPath(Utils::getMostDistantNode($node->getPreceders()))
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

    static private function getChainNodes(Node $node, array &$chainNodes): void
    {
        $chainNodes[] = $node;

        $preceders = $node->getPreceders();
        if (empty($preceders)) {
            return;
        }

        $lengths = self::getLengthsPerNodeName($preceders);
        $nodes = self::getNodesPerNodeName($preceders);

        arsort($lengths);
        $longestNodeName = array_key_first($lengths);

        self::getChainNodes($nodes[$longestNodeName], $chainNodes);
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

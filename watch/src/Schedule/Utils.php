<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\MilestoneBuffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Project;
use Watch\Schedule\Model\ProjectBuffer;

class Utils
{
    static public function getDuplicate(Node $origin): Node
    {
        return self::getDuplicateRecursively($origin);
    }

    static public function getCriticalChain(Project $origin): Node
    {
        $copy = self::getDuplicate($origin);
        /** @var ProjectBuffer $projectBuffer */
        $projectBuffer = current(array_filter(
            $copy->getPreceders(),
            fn(Node $node) => $node instanceof ProjectBuffer,
        ));
        foreach (
            array_filter(
                $projectBuffer->getPreceders(true),
                fn(Node $preceder) => $preceder instanceof FeedingBuffer,
            ) as $feedingBuffer) {
            foreach ($feedingBuffer->getFollowLinks() as $link) {
                $feedingBuffer->unprecede($link->node);
            }
        }
        self::cropBranches($projectBuffer);
        return current($projectBuffer->getPreceders());
    }

    /**
     * @param Project $origin
     * @return Node[]
     */
    static public function getFeedingChains(Project $origin): array
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

        $feedingBuffers = array_filter(
            $nodes,
            fn(Node $node) => $node instanceof FeedingBuffer,
        );
        foreach ($feedingBuffers as $feedingBuffer) {
            self::cropBranches($feedingBuffer);
        }

        return array_reduce(
            $feedingBuffers,
            fn($acc, Node $feedingBuffer) => [
                ...$acc,
                $feedingBuffer->name => current($feedingBuffer->getPreceders())
            ],
            [],
        );
    }

    static public function getMilestoneChains(Project $origin): array
    {
        /** @var Node[] $nodes */
        $nodes = array_reduce(
            self::getDuplicate($origin)->getPreceders(true),
            fn($acc, Node $node) => [...$acc, $node->name => $node],
            []
        );

        $milestoneBuffers = array_filter(
            $nodes,
            fn(Node $node) => $node instanceof MilestoneBuffer,
        );
        foreach ($milestoneBuffers as $milestoneBuffer) {
            self::cropBranches($milestoneBuffer);
        }

        return array_reduce(
            $milestoneBuffers,
            fn($acc, Node $milestoneBuffer) => [
                ...$acc,
                $milestoneBuffer->name => current($milestoneBuffer->getPreceders())
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

        $nodeLengths = array_reduce(
            $preceders,
            fn($acc, Node $preceder) => [...$acc, $preceder->name => $preceder->getLength(true)],
            [],
        );
        arsort($nodeLengths);
        $longestNodeName = array_key_first($nodeLengths);

        foreach ($preceders as $preceder) {
            if ($preceder->name !== $longestNodeName) {
                $preceder->unprecede($node);
                continue;
            }
            self::cropBranches($preceder);
        }
    }

    /**
     * @param Node $chain
     * @return Node[]
     */
    static public function getPath(Node $chain): array
    {
        return array_reduce(
            [$chain, ...$chain->getPreceders(true)],
            fn($acc, Node $node) => [...$acc, $node],
            [],
        );
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

    static public function getPathLateDays(array $path, \DateTimeImmutable $now): int
    {
        return array_reduce(
            $path,
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

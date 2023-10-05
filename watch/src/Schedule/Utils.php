<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;

class Utils
{
    /**
     * @param Node[] $nodes
     * @return Node
     */
    static public function getLongestSequence(array $nodes, array $types = []): Node
    {
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
     * @return Node
     */
    static public function getShortestSequence(array $nodes, array $types = []): Node
    {
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) > $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    static public function getUnique(array $nodes): array
    {
        $hash = [];
        foreach ($nodes as $node) {
            $hash[$node->getName()] = $node;
        }
        return array_values($hash);
    }

    static public function getMilestone(array $issues): Milestone
    {
        $nodes = [];
        foreach ($issues as $issue) {
            $node = new Node($issue['key'], $issue['duration']);
            $nodes[$node->getName()] = $node;
        }

        $milestone = new Milestone('finish');
        foreach ($issues as $issue) {
            $inwards = $issue['links']['inward'];
            foreach ($inwards as $link) {
                $follower = $nodes[$link['key']] ?? null;
                $preceder = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder, $link['type']);
                }
            }
            if (empty($inwards)) {
                $node = $nodes[$issue['key']] ?? null;
                if (!is_null($nodes)) {
                    $milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        return $milestone;
    }
}

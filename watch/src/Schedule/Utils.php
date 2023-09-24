<?php

namespace Watch\Schedule;

class Utils
{
    /**
     * @param Node[] $nodes
     * @return Node
     */
    static public function getLongestSequence(array $nodes): Node
    {
        $types = [Link::TYPE_SEQUENCE];
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
    static public function getShortestSequence(array $nodes): Node
    {
        $types = [Link::TYPE_SEQUENCE];
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) > $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    static public function getMilestone(array $issues): Milestone
    {
        $nodes = [];
        foreach ($issues as $issue) {
            $node = new Node($issue['key'], $issue['estimatedDuration']);
            $nodes[$node->getName()] = $node;
        }

        $milestone = new Milestone('finish');
        foreach ($issues as $issue) {
            $inwards = array_filter($issue['links']['inward'], fn($link) => $link['type'] === 'Depends');
            foreach ($inwards as $link) {
                $follower = $nodes[$link['key']] ?? null;
                $preceder = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder);
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

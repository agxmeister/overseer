<?php

namespace Watch\Schedule;

class Utils
{
    /**
     * @param Node[] $nodes
     * @return Node
     */
    static public function getLongestNode(array $nodes): Node
    {
        $types = [Link::TYPE_SEQUENCE];
        return array_reduce(
            $nodes,
            fn(Node|null $acc, Node $node) => is_null($acc) ?
                $node : (
                $acc->getDistance(true, $types) < $node->getDistance(true, $types) ? $node : $acc
                )
        );
    }

    /**
     * @param Node[] $nodes
     * @return Node
     */
    static public function getShortestNode(array $nodes): Node
    {
        $types = [Link::TYPE_SEQUENCE];
        return array_reduce(
            $nodes,
            fn(Node|null $acc, Node $node) => is_null($acc) ?
                $node : (
                $acc->getDistance(true, $types) > $node->getDistance(true, $types) ? $node : $acc
                )
        );
    }
}

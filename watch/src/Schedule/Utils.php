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
        return array_reduce(
            $nodes,
            fn(Node|null $acc, Node $node) => is_null($acc) ?
                $node : (
                $acc->getDistance(true) < $node->getDistance(true) ? $node : $acc
                )
        );
    }

    /**
     * @param Node[] $nodes
     * @return Node
     */
    static public function getShortestNode(array $nodes): Node
    {
        return array_reduce(
            $nodes,
            fn(Node|null $acc, Node $node) => is_null($acc) ?
                $node : (
                $acc->getDistance(true) > $node->getDistance(true) ? $node : $acc
                )
        );
    }
}

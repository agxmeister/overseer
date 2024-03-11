<?php

namespace Watch\Schedule\Model;

class Project extends Batch
{
    /**
     * @return Milestone[]
     */
    public function getMilestones(): array
    {
        return array_filter(
            $this->getNodes(),
            fn(Node $node) => $node instanceof Milestone,
        );
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        $nodes = [];
        self::getNodesRecursively($this, $nodes);
        return $nodes;
    }

    private function getNodesRecursively(Node $node, array &$nodes): void
    {
        if (isset($nodes[$node->name])) {
            return;
        }
        $nodes[$node->name] = $node;
        foreach ($node->getPreceders() as $preceder) {
            self::getNodesRecursively($preceder, $nodes);
        }
        foreach ($node->getFollowers() as $follower) {
            self::getNodesRecursively($follower, $nodes);
        }
    }
}

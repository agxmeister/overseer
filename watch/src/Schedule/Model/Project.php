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
            $this->getLinkedNodes(),
            fn(Node $node) => $node instanceof Milestone,
        );
    }
}

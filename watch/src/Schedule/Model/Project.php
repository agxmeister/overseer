<?php

namespace Watch\Schedule\Model;

class Project extends Batch
{
    /**
     * @var Milestone[]
     */
    private array $milestones = [];

    public function addMilestone(Milestone $milestone): void
    {
        $this->milestones[] = $milestone;
    }

    /**
     * @return Milestone[]
     */
    public function getMilestones(): array
    {
        return $this->milestones;
    }
}

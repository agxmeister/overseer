<?php

namespace Watch\Schedule\Model;

class Project
{
    /**
     * @var Milestone[]
     */
    private array $milestones;

    public function addMilestone(Milestone $milestone): void
    {
        $this->milestones[] = $milestone;
    }

    public function getFinalMilestone(): Milestone
    {
        return reset($this->milestones);
    }

    /**
     * @return Milestone[]
     */
    public function getMilestones(): array
    {
        return $this->milestones;
    }
}

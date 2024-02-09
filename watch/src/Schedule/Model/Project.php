<?php

namespace Watch\Schedule\Model;

class Project extends Node
{
    /**
     * @var Milestone[]
     */
    private array $milestones = [];

    public function __construct(string $name)
    {
        parent::__construct($name, 0);
    }

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

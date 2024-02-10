<?php

namespace Watch;

use Watch\Schedule\Model\Project;

class Schedule
{
    private Project $project;

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): void
    {
        $this->project = $project;
    }
}

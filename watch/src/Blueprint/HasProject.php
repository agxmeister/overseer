<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Milestone;

trait HasProject
{
    public function getProjectName(): string
    {
        return $this->getProject()?->key;
    }

    protected function getProject(): Milestone|null
    {
        return array_reduce(
            array_filter(
                $this->milestones,
                fn($line) => $line instanceof Milestone,
            ),
            fn($acc, $line) => $line,
        );
    }
}

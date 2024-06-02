<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Milestone;

trait HasMilestones
{
    /**
     * @return string[]
     */
    public function getMilestoneNames(): array
    {
        return array_map(
            fn(Milestone $milestone) => $milestone->key,
            $this->getMilestones()
        );
    }

    /**
     * @return Milestone[]
     */
    protected function getMilestones(): array
    {
        return array_slice(array_values(
            array_filter(
                $this->milestones,
                fn($line) => get_class($line) === Milestone::class,
            )
        ), 0, -1);
    }
}

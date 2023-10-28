<?php

namespace Watch\Schedule;

readonly class Director
{
    public function __construct(private Builder $builder)
    {
    }

    public function build(): Builder
    {
        return $this->builder
            ->run()
            ->addMilestone()
            ->addMilestoneBuffer()
            ->addFeedingBuffers()
            ->addDates();
    }
}

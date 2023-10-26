<?php

namespace Watch\Schedule;

class Director
{
    public function __construct(private readonly Builder $builder)
    {
    }

    public function build(): Builder
    {
        return $this->builder
            ->run()
            ->addCriticalChain()
            ->addMilestoneBuffer()
            ->addFeedingBuffers()
            ->addDates()
            ->addLinks();
    }
}

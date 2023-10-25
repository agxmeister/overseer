<?php

namespace Watch\Schedule;

use Watch\Schedule\Strategy\Limit\Strategy as LimitStrategy;
use Watch\Schedule\Strategy\Schedule\Strategy as ScheduleStrategy;

class Director
{
    public function __construct(private readonly Builder $builder)
    {
    }

    public function get(array $issues): array
    {
        return $this->builder
            ->run($issues)
            ->addCriticalChain()
            ->addMilestoneBuffer()
            ->addFeedingBuffers()
            ->addIssuesDates()
            ->addBuffersDates()
            ->addLinks()
            ->release();
    }

    public function create(array $issues, LimitStrategy $limitStrategy, ScheduleStrategy $scheduleStrategy): array
    {
        return $this->builder
            ->run($issues)
            ->setLimit($limitStrategy)
            ->addCriticalChain()
            ->addMilestoneBuffer()
            ->addFeedingBuffers()
            ->setSchedule($scheduleStrategy)
            ->addIssuesDates()
            ->addBuffersDates()
            ->addLinks()
            ->release();
    }
}

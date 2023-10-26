<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;

class FromScratch extends Builder
{
    public function __construct(array $issues, private readonly LimitStrategy $limitStrategy, private readonly ScheduleStrategy $scheduleStrategy)
    {
        parent::__construct($issues);
    }

    public function run(): self
    {
        parent::run();
        $this->limitStrategy->apply($this->milestone);
        return $this;
    }

    public function addDates(): self
    {
        $this->scheduleStrategy->apply($this->milestone);
        parent::addDates();
        return $this;
    }
}

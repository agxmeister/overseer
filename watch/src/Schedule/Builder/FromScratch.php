<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class FromScratch extends Builder
{
    public function __construct(array $issues, \DateTimeInterface $now, ScheduleStrategy $scheduleStrategy, private readonly LimitStrategy $limitStrategy)
    {
        parent::__construct($issues, $now, $scheduleStrategy);
    }

    public function addMilestone(): self
    {
        $this->milestone = Utils::getMilestone($this->issues, $this->limitStrategy);
        return $this;
    }
}

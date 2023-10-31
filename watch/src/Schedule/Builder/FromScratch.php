<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class FromScratch extends Builder
{
    public function __construct(array $issues, \DateTimeInterface $now, private readonly LimitStrategy $limitStrategy, private readonly ScheduleStrategy $scheduleStrategy)
    {
        parent::__construct($issues, $now);
    }

    public function addMilestone(): self
    {
        $this->milestone = Utils::getMilestone($this->issues, $this->limitStrategy);
        return $this;
    }

    public function addDates(): self
    {
        Utils::setDates($this->milestone, $this->scheduleStrategy);
        return $this;
    }
}

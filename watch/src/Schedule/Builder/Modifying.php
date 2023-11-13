<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class Modifying extends Builder
{
    public function __construct(Context $context, array $issues, private readonly ScheduleStrategy $scheduleStrategy, private readonly LimitStrategy $limitStrategy)
    {
        parent::__construct($context, $issues);
    }

    public function addMilestone(): self
    {
        $this->milestone = Utils::getMilestone($this->issues, $this->limitStrategy);
        return $this;
    }

    public function addDates(): self
    {
        $this->scheduleStrategy->apply($this->milestone);
        return $this;
    }
}

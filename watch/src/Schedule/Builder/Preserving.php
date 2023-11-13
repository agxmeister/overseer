<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class Preserving extends Builder
{
    public function __construct(Context $context, array $issues, private readonly ScheduleStrategy $scheduleStrategy)
    {
        parent::__construct($context, $issues);
    }

    public function addMilestone(): self
    {
        $this->milestone = Utils::getMilestone($this->issues);
        return $this;
    }

    public function addDates(): self
    {
        $this->scheduleStrategy->apply($this->milestone);
        return $this;
    }
}

<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class Modifying implements Builder
{
    use AbleToBuild;

    public function __construct(protected readonly Context $context, protected readonly array $issues, private readonly ScheduleStrategy $scheduleStrategy, private readonly LimitStrategy $limitStrategy)
    {
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

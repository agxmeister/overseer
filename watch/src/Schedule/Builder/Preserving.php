<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Builder;
use Watch\Schedule\Utils;

class Preserving implements Builder
{
    use AbleToBuild;

    public function __construct(protected readonly Context $context, protected readonly array $issues, private readonly ScheduleStrategy $scheduleStrategy)
    {
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

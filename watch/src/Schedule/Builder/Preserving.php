<?php

namespace Watch\Schedule\Builder;

use Watch\Subject\Model\Issue;
use Watch\Schedule\Builder;

class Preserving implements Builder
{
    use AbleToBuild;

    /**
     * @param Context $context
     * @param Issue[] $issues
     * @param LimitStrategy|null $limitStrategy
     * @param ScheduleStrategy|null $scheduleStrategy
     */
    public function __construct(
        protected readonly Context $context,
        protected readonly array $issues,
        private readonly LimitStrategy|null $limitStrategy = null,
        private readonly ScheduleStrategy|null $scheduleStrategy = null,
    )
    {
    }
}

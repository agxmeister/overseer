<?php

namespace Watch\Schedule\Builder;

use Watch\Subject\Model\Issue;
use Watch\Schedule\Builder;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Node;

class Modifying implements Builder
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

    public function addDates(): self
    {
        $this->scheduleStrategy->apply($this->milestone);
        $this->addBuffersDates(array_filter(
            $this->milestone->getPreceders(true),
            fn(Node $node) => $node instanceof Buffer,
        ));
        $this->addMilestoneDates($this->milestone);
        return $this;
    }
}

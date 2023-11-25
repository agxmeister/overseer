<?php

namespace Watch\Schedule\Builder;

use Watch\Issue;
use Watch\Schedule\Builder;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

class Modifying implements Builder
{
    use AbleToBuild;

    /**
     * @param Context $context
     * @param Issue[] $issues
     * @param LimitStrategy $limitStrategy
     * @param ScheduleStrategy $scheduleStrategy
     */
    public function __construct(protected readonly Context $context, protected readonly array $issues, private readonly LimitStrategy $limitStrategy, private readonly ScheduleStrategy $scheduleStrategy)
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
        $this->addBuffersDates(array_filter(
            $this->milestone->getPreceders(true),
            fn(Node $node) => $node instanceof Buffer,
        ));
        $this->addMilestoneDates($this->milestone);
        return $this;
    }
}

<?php

namespace Watch\Schedule\Builder\Strategy\Schedule;

use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

readonly class FromDate implements ScheduleStrategy
{
    public function __construct(private \DateTimeImmutable $date)
    {
    }

    public function apply(Node $milestone): void
    {
        $milestoneLength = Utils::getLongestSequence($milestone->getPreceders())->getLength(true);
        $toDate = $this->date->modify("{$milestoneLength} day");
        foreach ($milestone->getPreceders(true) as $node) {
            $node->setAttribute('begin', $toDate
                ->modify("-{$node->getDistance()} day")
                ->format("Y-m-d"));
            $node->setAttribute('end', $toDate
                ->modify("-{$node->getCompletion()} day")
                ->format("Y-m-d"));
        }
        $milestone->setAttribute('begin', $this->date->format("Y-m-d"));
        $milestone->setAttribute('end', $toDate->format("Y-m-d"));
    }
}

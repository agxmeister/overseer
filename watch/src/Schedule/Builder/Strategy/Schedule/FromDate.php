<?php

namespace Watch\Schedule\Builder\Strategy\Schedule;

use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Utils;

readonly class FromDate implements ScheduleStrategy
{
    public function __construct(private \DateTimeImmutable $date)
    {
    }

    public function apply(Node $milestone): void
    {
        $milestoneLength = Utils::getMostDistantNode($milestone->getPreceders())->getLength(true);
        $milestoneEndDate = $this->date->modify("{$milestoneLength} day");
        foreach (
            array_filter(
                $milestone->getPreceders(true),
                fn(Node $node) => $node instanceof Issue,
            ) as $node
        ) {
            $node
                ->setAttribute(
                    'begin',
                    $milestoneEndDate->modify("-{$node->getDistance()} day")->format("Y-m-d")
                )
                ->setAttribute(
                    'end',
                    $milestoneEndDate->modify("-{$node->getCompletion()} day")->format("Y-m-d")
                );
        }
    }
}

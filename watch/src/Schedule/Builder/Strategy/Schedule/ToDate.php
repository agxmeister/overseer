<?php

namespace Watch\Schedule\Builder\Strategy\Schedule;

use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;

readonly class ToDate implements ScheduleStrategy
{
    public function __construct(private \DateTimeImmutable $date)
    {
    }

    public function apply(Node $milestone): void
    {
        foreach (
            array_filter(
                $milestone->getPreceders(true),
                fn(Node $node) => $node instanceof Issue,
            ) as $node
        ) {
            $node->setAttribute('begin', $this->date
                ->modify("-{$node->getDistance()} day")
                ->format("Y-m-d"));
            $node->setAttribute('end', $this->date
                ->modify("-{$node->getCompletion()} day")
                ->format("Y-m-d"));
        }
    }
}

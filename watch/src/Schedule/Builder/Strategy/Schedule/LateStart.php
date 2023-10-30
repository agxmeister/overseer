<?php

namespace Watch\Schedule\Builder\Strategy\Schedule;

use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Node;

readonly class LateStart implements ScheduleStrategy
{
    public function __construct(private \DateTimeImmutable $date)
    {
    }

    public function apply(Node $milestone): void
    {
        foreach ($milestone->getPreceders(true) as $node) {
            $node->setAttribute('begin', $this->date
                ->modify("-{$node->getDistance()} day")
                ->format("Y-m-d"));
            $node->setAttribute('end', $this->date
                ->modify("-{$node->getCompletion()} day")
                ->format("Y-m-d"));
        }
        $milestone->setAttribute('begin', array_reduce(
            $milestone->getPreceders(true),
            fn($acc, Node $node) => min($acc, $node->getAttribute('begin')),
            $this->date->format("Y-m-d"),
        ));
        $milestone->setAttribute('end', $this->date->format("Y-m-d"));
    }
}

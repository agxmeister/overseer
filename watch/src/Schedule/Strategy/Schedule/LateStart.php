<?php

namespace Watch\Schedule\Strategy\Schedule;

use Watch\Schedule\Model\Node;

class LateStart implements Strategy
{
    public function __construct(private readonly \DateTimeImmutable $date)
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
    }
}

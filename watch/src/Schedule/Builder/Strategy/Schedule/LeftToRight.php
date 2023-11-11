<?php

namespace Watch\Schedule\Builder\Strategy\Schedule;

use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Node;

readonly class LeftToRight implements ScheduleStrategy
{
    public function apply(Node $milestone): void
    {
        $issues = array_filter($milestone->getPreceders(true), fn(Node $node) => $node instanceof Issue);
        usort(
            $issues,
            fn(Node $a, Node $b) =>
                $a->getDistance() < $b->getDistance() ? 1 : ($a->getDistance() > $b->getDistance() ? -1 : 0)
        );
        foreach ($issues as $issue) {
            $maxPrecedersEndDate = array_reduce(
                $issue->getPreceders(),
                fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
            );
            if (is_null($maxPrecedersEndDate)) {
                continue;
            }
            $date = (new \DateTimeImmutable($maxPrecedersEndDate));
            $issue->setAttribute('begin', $date->modify("1 day")->format("Y-m-d"));
            $issue->setAttribute('end', $date->modify("{$issue->getLength()} day")->format("Y-m-d"));
        }
        foreach (array_filter($milestone->getPreceders(true), fn(Node $node) => $node instanceof Buffer) as $buffer) {
            $date = new \DateTimeImmutable(max(array_map(
                fn(Node $node) => $node->getAttribute('end') ?? null,
                $buffer->getPreceders()
            )));
            $buffer->setAttribute('begin', $date->modify("1 day")->format("Y-m-d"));
            $buffer->setAttribute('end', $date->modify("{$buffer->getLength()} day")->format("Y-m-d"));
        }
        $end = (new \DateTimeImmutable(array_reduce(
            $milestone->getPreceders(true),
            fn($acc, Node $node) => max($acc, $node->getAttribute('end')),
        )))->modify("1 day");
        $milestone->setAttribute('begin', array_reduce(
            $milestone->getPreceders(true),
            fn($acc, Node $node) => min($acc, $node->getAttribute('begin')),
            $end->format("Y-m-d"),
        ));
        $milestone->setAttribute('end', $end->format("Y-m-d"));
    }
}

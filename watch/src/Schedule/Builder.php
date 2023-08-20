<?php

namespace Watch\Schedule;

use Watch\Schedule\Strategy\Strategy;

class Builder
{
    public function getSchedule(array $issues, string $date, Strategy $strategy): array
    {
        $nodes = [];
        foreach ($issues as $issue) {
            $node = new Node($issue['key'], $issue['estimatedDuration']);
            $nodes[$node->getName()] = $node;
        }

        $milestone = new Node('finish');
        foreach ($issues as $issue) {
            foreach ($issue['links']['inward'] as $link) {
                $preceder = $nodes[$link['key']] ?? null;
                $follower = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder);
                }
            }
            if (empty($issue['links']['outward'])) {
                $node = $nodes[$issue['key']] ?? null;
                if (!is_null($nodes)) {
                    $milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        $strategy->schedule($milestone);

        return array_map(function (array $issue) use ($nodes, $date) {
            /** @var Node $node */
            $node = $nodes[$issue['key']] ?? null;
            if (is_null($node)) {
                return $issue;
            }
            $distance = $node->getDistance();
            $completion = $node->getCompletion();
            $startDate = (new \DateTime($date))->modify("-{$distance} day");
            $finishDate = (new \DateTime($date))->modify("-{$completion} day");
            return [
                ...$issue,
                'estimatedStartDate' => $startDate->format("Y-m-d"),
                'estimatedFinishDate' => $finishDate->format("Y-m-d"),
            ];
        }, $issues);
    }
}

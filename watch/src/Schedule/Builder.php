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

        $milestone = new Milestone('finish');
        foreach ($issues as $issue) {
            foreach ($issue['links']['inward'] as $link) {
                $follower = $nodes[$link['key']] ?? null;
                $preceder = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder);
                }
            }
            if (empty($issue['links']['inward'])) {
                $node = $nodes[$issue['key']] ?? null;
                if (!is_null($nodes)) {
                    $milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        $strategy->schedule($milestone);

        return array_map(function (array $issue) use ($nodes, $date) {
            $result = [
                'key' => $issue['key'],
                'estimatedBeginDate' => $issue['estimatedBeginDate'],
                'estimatedEndDate' => $issue['estimatedEndDate'],
            ];

            /** @var Node $node */
            $node = $nodes[$issue['key']] ?? null;
            if (is_null($node)) {
                return $result;
            }

            $distance = $node->getDistance();
            $completion = $node->getCompletion();
            $startDate = (new \DateTime($date))->modify("-{$distance} day");
            $finishDate = (new \DateTime($date))->modify("-{$completion} day");
            $result['estimatedBeginDate'] = $startDate->format("Y-m-d");
            $result['estimatedEndDate'] = $finishDate->format("Y-m-d");

            $inwardLinks = array_values(array_map(
                fn(Node $preceder) => $preceder->getName(),
                array_filter(
                    $node->getPreceders(false, [Link::TYPE_SCHEDULE]),
                    fn(Node $node) => !is_a($node, Milestone::class)
                )
            ));
            $outwardLinks = array_values(array_map(
                fn(Node $follower) => $follower->getName(),
                array_filter(
                    $node->getFollowers([Link::TYPE_SCHEDULE]),
                    fn(Node $node) => !is_a($node, Milestone::class)
                )
            ));
            if ($inwardLinks || $outwardLinks) {
                $result['links'] = [];
                if ($inwardLinks) {
                    $result['links']['inward'] = $inwardLinks;
                }
                if ($outwardLinks) {
                    $result['links']['outward'] = $outwardLinks;
                }
            }

            return $result;
        }, $issues);
    }
}

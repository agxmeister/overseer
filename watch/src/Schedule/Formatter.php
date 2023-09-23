<?php

namespace Watch\Schedule;

class Formatter
{
    public function getSchedule(array $issues, Milestone $milestone, string $date): array
    {
        $nodes = $milestone->getPreceders(true);
        return array_map(function (array $issue) use ($nodes, $date) {
            $result = [
                'key' => $issue['key'],
                'estimatedBeginDate' => $issue['estimatedBeginDate'],
                'estimatedEndDate' => $issue['estimatedEndDate'],
            ];

            /** @var Node $node */
            $node = array_reduce($nodes, fn($acc, Node $node) => $node->getName() === $issue['key'] ? $node : $acc);
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
                fn(Node $follower) => [
                    'key' => $follower->getName(),
                    'type' => 'Follows',
                ],
                array_filter(
                    $node->getFollowers([Link::TYPE_SCHEDULE]),
                    fn(Node $node) => !is_a($node, Milestone::class)
                )
            ));
            $outwardLinks = array_values(array_map(
                fn(Node $preceder) => [
                    'key' => $preceder->getName(),
                    'type' => 'Follows',
                ],
                array_filter(
                    $node->getPreceders(false, [Link::TYPE_SCHEDULE]),
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

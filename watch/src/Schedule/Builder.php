<?php

namespace Watch\Schedule;

class Builder
{
    public function getSchedule(array $issues, string $date): array
    {
        $nodes = [];
        $milestoneNode = new Node('finish');

        foreach ($issues as $issue) {
            $node = new Node($issue['key'], $issue['estimatedDuration']);
            $nodes[$node->getName()] = $node;
            $milestoneNode->follow($node, Link::TYPE_SCHEDULE);
        }

        foreach ($issues as $issue) {
            foreach ($issue['links']['inward'] as $link) {
                $preceder = $nodes[$link['key']] ?? null;
                $follower = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder);
                }
            }
        }

        $point = 0;
        while ($point < 100) {
            $ongoingNodes = array_filter($milestoneNode->getPreceders(true), fn(Node $node) => $this->isOngoingAt($node, $point));
            $completingNodes = array_filter($milestoneNode->getPreceders(true), fn(Node $node) => $this->isCompletingAt($node, $point));
            $point++;
            if (empty($ongoingNodes) || empty($completingNodes)) {
                continue;
            }
            $numberOfTasksInParallel = count($ongoingNodes);
            while ($numberOfTasksInParallel > 2) {
                $longestNode = Utils::getLongestNode($completingNodes);
                $shortestNode = Utils::getShortestNode($ongoingNodes);
                if ($longestNode === $shortestNode) {
                    break;
                }
                $followers = $longestNode->getFollowers([Link::TYPE_SCHEDULE]);
                array_walk($followers, fn(Node $follower) => $longestNode->unprecede($follower));
                $longestNode->precede($shortestNode, Link::TYPE_SCHEDULE);
                $ongoingNodes = array_filter($ongoingNodes, fn(Node $node) => $node !== $longestNode);
                $completingNodes = array_filter($completingNodes, fn(Node $node) => $node !== $longestNode);
                $numberOfTasksInParallel--;
            }
        }

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

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() <= $point && $node->getDistance() > $point;
    }
    private function isCompletingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() === $point;
    }
}

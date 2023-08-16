<?php

namespace Watch\Schedule;

class Builder
{
    public function getGraph($issues): Node
    {
        $milestoneNode = new Node('finish');
        foreach ($issues as $issue) {
            $issueNode = new Node($issue['key'], $issue['estimatedDuration']);
            $milestoneNode->follow($issueNode);
        }
        $point = 0;
        while ($point < 100) {
            $nodes = array_filter($milestoneNode->getPreceders(true), fn(Node $node) => $this->isOngoingAt($node, $point));
            $numberOfTasksInParallel = count($nodes);
            while ($numberOfTasksInParallel > 2) {
                $longestNode = Utils::getLongestNode($nodes);
                $shortestNode = Utils::getShortestNode($nodes);
                $longestNode->precede($shortestNode);
                $numberOfTasksInParallel--;
            }
            $point++;
        }
        return $milestoneNode;
    }

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() <= $point && $node->getDistance() > $point;
    }
}

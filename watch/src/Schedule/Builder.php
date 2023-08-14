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
        do {
            $nodes = array_filter($milestoneNode->getPreceders(true), fn(Node $node) => $node->getFinish() <= $point);
            $node = $milestoneNode->getLongestPreceder();
            $node->unprecede($milestoneNode);
            $node->precede($milestoneNode->getShortestPreceder());
        } while (count($nodes) - 1 > 2);
        return $milestoneNode;
    }
}

<?php

namespace Watch\Schedule\Builder\Strategy\Limit;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

readonly class Basic implements LimitStrategy
{
    public function apply(Node $milestone): void
    {
        $point = 1;
        do {
            $ongoingNodes = array_filter($milestone->getPreceders(true), fn(Node $node) => $this->isOngoingAt($node, $point));
            $completingNodes = array_filter($milestone->getPreceders(true), fn(Node $node) => $this->isCompletingAt($node, $point));
            $numberOfTasksInParallel = count($ongoingNodes);
            while ($numberOfTasksInParallel > 2) {
                $longestNode = Utils::getLongestSequence($completingNodes, [Link::TYPE_SEQUENCE]);
                $shortestNode = Utils::getShortestSequence(
                    array_filter($ongoingNodes, fn(Node $node) => $node->getName() !== $longestNode->getName()),
                    [Link::TYPE_SEQUENCE]
                );
                if (is_null($longestNode) || is_null($shortestNode)) {
                    break;
                }
                $followers = $longestNode->getFollowers([Link::TYPE_SCHEDULE]);
                array_walk($followers, fn(Node $follower) => $longestNode->unprecede($follower));
                $longestNode->precede($shortestNode, Link::TYPE_SCHEDULE);
                $ongoingNodes = array_filter($ongoingNodes, fn(Node $node) => $node !== $longestNode);
                $completingNodes = array_filter($completingNodes, fn(Node $node) => $node !== $longestNode);
                $numberOfTasksInParallel--;
            }
            $point++;
        } while ($point <= $milestone->getLength(true));
    }

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() <= $point && $node->getDistance() >= $point;
    }

    private function isCompletingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() === $point;
    }
}

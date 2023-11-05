<?php

namespace Watch\Schedule\Builder\Strategy\Limit;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

readonly class Corrective implements LimitStrategy
{
    public function apply(Node $milestone): void
    {
        $nodes = $milestone->getPreceders(true);
        $shift = 0;
        do {
            $point = $milestone->getDistance(true) - $shift;
            $ongoingNodes = array_filter($nodes, fn(Node $node) => $this->isOngoingAt($node, $point));
            while (sizeof($ongoingNodes) > 2) {
                $shortestNode = Utils::getShortestSequence($ongoingNodes, [Link::TYPE_SEQUENCE]);
                $longestNode = Utils::getLongestSequence(
                    array_filter($ongoingNodes, fn(Node $node) => $node->getName() !== $shortestNode->getName()),
                    [Link::TYPE_SEQUENCE]
                );
                if (is_null($shortestNode) || is_null($longestNode)) {
                    break;
                }
                $followers = $shortestNode->getFollowers([Link::TYPE_SCHEDULE]);
                array_walk($followers, fn(Node $follower) => $shortestNode->unprecede($follower, Link::TYPE_SCHEDULE));
                $preceders = $longestNode->getPreceders(false, [Link::TYPE_SCHEDULE]);
                array_walk($preceders, fn(Node $preceder) => $longestNode->unfollow($preceder, Link::TYPE_SCHEDULE));
                $longestNode->follow($shortestNode, Link::TYPE_SCHEDULE);
                $ongoingNodes = array_filter($ongoingNodes, fn(Node $node) => $node !== $longestNode);
            }
            $shift++;
        } while ($shift <= $milestone->getLength(true));
    }

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() <= $point && $node->getDistance() >= $point;
    }
}

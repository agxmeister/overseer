<?php

namespace Watch\Schedule\Builder\Strategy\Limit;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Utils;

readonly class Initiative implements LimitStrategy
{
    public function __construct(private int $limit)
    {
    }

    public function apply(Node $milestone): void
    {
        $nodes = $milestone->getPreceders(true);
        $point = 1;
        do {
            $ongoingNodes = array_filter($nodes, fn(Node $node) => $this->isOngoingAt($node, $point));
            $completingNodes = array_filter($nodes, fn(Node $node) => $this->isCompletingAt($node, $point));
            while (sizeof($ongoingNodes) > $this->limit) {
                $mostDistantNode = Utils::getMostDistantNode($completingNodes, [Link::TYPE_SEQUENCE]);
                if (is_null($mostDistantNode)) {
                    break;
                }
                $leastDistantNode = Utils::getLeastDistantNode(
                    array_filter($ongoingNodes, fn(Node $node) => $node->name !== $mostDistantNode->name),
                    [Link::TYPE_SEQUENCE]
                );
                if (is_null($leastDistantNode)) {
                    break;
                }
                $followers = $mostDistantNode->getFollowers([Link::TYPE_SCHEDULE]);
                array_walk($followers, fn(Node $follower) => $mostDistantNode->unprecede($follower, Link::TYPE_SCHEDULE));
                $mostDistantNode->precede($leastDistantNode, Link::TYPE_SCHEDULE);
                $ongoingNodes = array_filter($ongoingNodes, fn(Node $node) => $node !== $mostDistantNode);
                $completingNodes = array_filter($completingNodes, fn(Node $node) => $node !== $mostDistantNode);
            }
            $point++;
        } while ($point <= $milestone->getLength(true));
    }

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() < $point && $node->getDistance() >= $point;
    }

    private function isCompletingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() + 1 === $point;
    }
}

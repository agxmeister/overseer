<?php

namespace Watch\Schedule\Builder\Strategy\Limit;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Utils;

readonly class Corrective implements LimitStrategy
{
    public function __construct(private int $limit)
    {
    }

    public function apply(Node $milestone): void
    {
        $nodes = array_filter($milestone->getPreceders(true), fn(Node $node) => $node->getAttribute('state') !== Issue::STATE_COMPLETED);
        $shift = 0;
        do {
            $point = $milestone->getDistance(true) - $shift;
            $ongoingNodes = array_filter($nodes, fn(Node $node) => $this->isOngoingAt($node, $point));
            while (sizeof($ongoingNodes) > $this->limit) {
                $leastDistantNode = Utils::getLeastDistantNode($ongoingNodes, [Link::TYPE_SEQUENCE]);
                if (is_null($leastDistantNode)) {
                    break;
                }
                $mostDistantNode = Utils::getMostDistantNode(
                    array_filter(
                        array_filter(
                            $ongoingNodes,
                            fn(Node $node) => $node->getAttribute('state') !== Issue::STATE_STARTED,
                        ),
                        fn(Node $node) => $node->name !== $leastDistantNode->name
                    ),
                    [Link::TYPE_SEQUENCE]
                );
                if (is_null($mostDistantNode)) {
                    break;
                }
                $followers = $leastDistantNode->getFollowers([Link::TYPE_SCHEDULE]);
                array_walk($followers, fn(Node $follower) => $leastDistantNode->unprecede($follower, Link::TYPE_SCHEDULE));
                $preceders = $mostDistantNode->getPreceders(false, [Link::TYPE_SCHEDULE]);
                array_walk($preceders, fn(Node $preceder) => $mostDistantNode->unfollow($preceder, Link::TYPE_SCHEDULE));
                $mostDistantNode->follow($leastDistantNode, Link::TYPE_SCHEDULE);
                $ongoingNodes = array_filter($ongoingNodes, fn(Node $node) => $node !== $mostDistantNode);
            }
            $shift++;
        } while ($shift <= $milestone->getLength(true));

        foreach (
            array_filter(
                $milestone->getPreceders(true),
                fn(Node $node) => $node->getAttribute('state') === Issue::STATE_COMPLETED,
            ) as $node
        ) {
            $node->setAttribute('ignored', true);
        }
    }

    private function isOngoingAt(Node $node, int $point): bool
    {
        return $node->getCompletion() < $point && $node->getDistance() >= $point;
    }
}

<?php

namespace Watch\Schedule;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Builder\ScheduleStrategy;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\FeedingBuffer;
use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;

class Utils
{
    /**
     * @param Node|null $node
     * @return Node[]
     */
    static public function getCriticalChain(Node|null $node): array
    {
        if (is_null($node)) {
            return [];
        }
        return [$node, ...self::getCriticalChain(Utils::getLongestSequence(
            array_filter(
                array_filter(
                    $node->getPreceders(),
                    fn($node) => get_class($node) !== FeedingBuffer::class,
                ),
                fn(Node $node) => !array_reduce(
                    $node->getFollowers(),
                    fn($acc, Node $node) => $acc || get_class($node) === FeedingBuffer::class,
                    false,
                ),
            )
        ))];
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getLongestSequence(array $nodes, array $types = []): Node|null
    {
        if (sizeof($nodes) === 0) {
            return null;
        }
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) < $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    /**
     * @param Node[] $nodes
     * @param array $types
     * @return Node|null
     */
    static public function getShortestSequence(array $nodes, array $types = []): Node|null
    {
        if (sizeof($nodes) === 0) {
            return null;
        }
        return array_reduce($nodes, fn(Node|null $acc, Node $node) => is_null($acc) ?
            $node : (
            $acc->getDistance(true, $types) > $node->getDistance(true, $types) ?
                $node :
                $acc
            )
        );
    }

    static public function getMilestone(array $issues, LimitStrategy $strategy = null): Milestone
    {
        $nodes = [];
        foreach ($issues as $issue) {
            $node = new Issue($issue['key'], $issue['duration'], [
                'begin' => $issue['begin'],
                'end' => $issue['end'],
                'isCompleted' => $issue['isCompleted'],
            ]);
            $nodes[$node->getName()] = $node;
        }

        $milestone = new Milestone('finish');
        foreach ($issues as $issue) {
            $inwards = $issue['links']['inward'];
            foreach ($inwards as $link) {
                $follower = $nodes[$link['key']] ?? null;
                $preceder = $nodes[$issue['key']] ?? null;
                if (!is_null($preceder) && !is_null($follower)) {
                    $follower->follow($preceder, $link['type']);
                }
            }
            if (empty($inwards)) {
                $node = $nodes[$issue['key']] ?? null;
                if (!is_null($nodes)) {
                    $milestone->follow($node, Link::TYPE_SCHEDULE);
                }
            }
        }

        if (!is_null($strategy)) {
            $strategy->apply($milestone);
        }

        return $milestone;
    }
}

<?php

namespace Watch\Action\Util;

use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Model\Node;
use Watch\Schedule\Model\Task;
use Watch\Schedule\Utils;

class Schedule
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_BUFFERS = 'buffers';
    const VOLUME_MILESTONES = 'milestones';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    public function serialize(Milestone $milestone): array
    {
        return [
            self::VOLUME_ISSUES => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter($milestone->getPreceders(true), fn(Node $node) => $node instanceof Task)
            )),
            self::VOLUME_BUFFERS => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                    'consumption' => $node->getAttribute('consumption'),
                ],
                array_filter($milestone->getPreceders(true), fn(Node $node) => $node instanceof Buffer)
            )),
            self::VOLUME_MILESTONES => array_values(array_map(
                fn(Node $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter([$milestone, ...$milestone->getPreceders(true)], fn(Node $node) => $node instanceof Milestone)
            )),
            self::VOLUME_LINKS => \Watch\Utils::getUnique(
                array_reduce(
                    $milestone->getPreceders(true),
                    fn($acc, Node $node) => [
                        ...$acc,
                        ...array_map(fn(Link $link) => [
                            'from' => $node->getName(),
                            'to' => $link->getNode()->getName(),
                            'type' => $link->getType(),
                        ], $node->getFollowLinks()),
                        ...array_map(fn(Link $link) => [
                            'from' => $link->getNode()->getName(),
                            'to' => $node->getName(),
                            'type' => $link->getType(),
                        ], $node->getPrecedeLinks()),
                    ],
                    []
                ),
                fn($link) => implode('-', array_values($link))
            ),
            self::VOLUME_CRITICAL_CHAIN => array_reduce(
                array_filter(Utils::getCriticalChain($milestone), fn(Node $node) => !($node instanceof Buffer)),
                fn($acc, Node $node) => [...$acc, $node->getName()],
                []
            ),
        ];
    }
}

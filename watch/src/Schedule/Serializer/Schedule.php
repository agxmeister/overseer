<?php

namespace Watch\Schedule\Serializer;

use Watch\Schedule\Model\Buffer as BufferModel;
use Watch\Schedule\Model\Issue as IssueModel;
use Watch\Schedule\Model\Link as LinkModel;
use Watch\Schedule\Model\Node as NodeModel;
use Watch\Schedule\Model\Schedule as ScheduleModel;
use Watch\Schedule\Utils;

readonly class Schedule
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_BUFFERS = 'buffers';
    const VOLUME_MILESTONES = 'milestones';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    public function serialize(ScheduleModel $schedule): array
    {
        $milestone = current($schedule->milestones);
        return [
            self::VOLUME_ISSUES => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter($milestone->getPreceders(true), fn(NodeModel $node) => $node instanceof IssueModel)
            )),
            self::VOLUME_BUFFERS => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                    'consumption' => $node->getAttribute('consumption'),
                ],
                array_filter($milestone->getPreceders(true), fn(NodeModel $node) => $node instanceof BufferModel)
            )),
            self::VOLUME_MILESTONES => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->getName(),
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                $schedule->milestones
            )),
            self::VOLUME_LINKS => \Watch\Utils::getUnique(
                array_reduce(
                    $milestone->getPreceders(true),
                    fn($acc, NodeModel $node) => [
                        ...$acc,
                        ...array_map(fn(LinkModel $link) => [
                            'from' => $node->getName(),
                            'to' => $link->getNode()->getName(),
                            'type' => $link->getType(),
                        ], $node->getFollowLinks()),
                        ...array_map(fn(LinkModel $link) => [
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
                array_filter(Utils::getCriticalChain($milestone), fn(NodeModel $node) => !($node instanceof BufferModel)),
                fn($acc, NodeModel $node) => [...$acc, $node->getName()],
                []
            ),
        ];
    }
}

<?php

namespace Watch\Schedule\Serializer;

use Watch\Schedule\Model\Buffer as BufferModel;
use Watch\Schedule\Model\Issue as IssueModel;
use Watch\Schedule\Model\Link as LinkModel;
use Watch\Schedule\Model\Node as NodeModel;
use Watch\Schedule\Model\Project as ProjectModel;
use Watch\Schedule\Utils;

readonly class Project
{
    const VOLUME_ISSUES = 'issues';
    const VOLUME_BUFFERS = 'buffers';
    const VOLUME_MILESTONES = 'milestones';
    const VOLUME_LINKS = 'links';
    const VOLUME_CRITICAL_CHAIN = 'criticalChain';

    public function serialize(ProjectModel $project): array
    {
        return [
            self::VOLUME_ISSUES => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->name,
                    'length' => $node->length,
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter($project->getPreceders(true), fn(NodeModel $node) => $node instanceof IssueModel)
            )),
            self::VOLUME_BUFFERS => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->name,
                    'length' => $node->length,
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                    'consumption' => $node->getAttribute('consumption'),
                ],
                \Watch\Utils::getUnique(
                    array_reduce(
                        [$project, ...$project->getMilestones()],
                        fn($acc, $milestone) => [
                            ...$acc,
                            ...array_filter($milestone->getPreceders(true), fn(NodeModel $node) => $node instanceof BufferModel)
                        ],
                        [],
                    ),
                    fn(BufferModel $buffer) => $buffer->name,
                ),
            )),
            self::VOLUME_MILESTONES => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->name,
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                [$project, ...$project->getMilestones()],
            )),
            self::VOLUME_LINKS => \Watch\Utils::getUnique(
                array_reduce(
                    $project->getPreceders(true),
                    fn($acc, NodeModel $node) => [
                        ...$acc,
                        ...array_map(fn(LinkModel $link) => [
                            'from' => $node->name,
                            'to' => $link->node->name,
                            'type' => $link->type,
                        ], $node->getFollowLinks()),
                        ...array_map(fn(LinkModel $link) => [
                            'from' => $link->node->name,
                            'to' => $node->name,
                            'type' => $link->type,
                        ], $node->getPrecedeLinks()),
                    ],
                    []
                ),
                fn($link) => implode('-', array_values($link))
            ),
            self::VOLUME_CRITICAL_CHAIN => array_reduce(
                array_filter(Utils::getLongestChain($project), fn(NodeModel $node) => !($node instanceof BufferModel)),
                fn($acc, NodeModel $node) => [...$acc, $node->name],
                []
            ),
        ];
    }
}

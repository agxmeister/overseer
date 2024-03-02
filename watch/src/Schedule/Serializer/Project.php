<?php

namespace Watch\Schedule\Serializer;

use Watch\Schedule\Model\Buffer as BufferModel;
use Watch\Schedule\Model\ProjectBuffer as ProjectBufferModel;
use Watch\Schedule\Model\MilestoneBuffer as MilestoneBufferModel;
use Watch\Schedule\Model\FeedingBuffer as FeedingBufferModel;
use Watch\Schedule\Model\Issue as IssueModel;
use Watch\Schedule\Model\Link as LinkModel;
use Watch\Schedule\Model\Milestone as MilestoneModel;
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
    const VOLUME_PROJECT = 'project';

    public function serialize(ProjectModel $project): array
    {
        return [
            self::VOLUME_ISSUES => array_values(array_map(
                fn(IssueModel $node) => [
                    'key' => $node->name,
                    'length' => $node->length,
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                array_filter($project->getPreceders(true), fn(NodeModel $node) => $node instanceof IssueModel)
            )),
            self::VOLUME_BUFFERS => array_values(array_map(
                fn(BufferModel $node) => [
                    'key' => $node->name,
                    'length' => $node->length,
                    'type' => $node->type,
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
            self::VOLUME_PROJECT => [
                'key' => $project->name,
                'begin' => $project->getAttribute('begin'),
                'end' => $project->getAttribute('end'),
            ],
            self::VOLUME_MILESTONES => array_values(array_map(
                fn(NodeModel $node) => [
                    'key' => $node->name,
                    'begin' => $node->getAttribute('begin'),
                    'end' => $node->getAttribute('end'),
                ],
                $project->getMilestones(),
            )),
            self::VOLUME_LINKS => \Watch\Utils::getUnique(
                array_reduce(
                    \Watch\Utils::getUnique(
                        [
                            ...$project->getPreceders(true),
                            ...array_reduce(
                                $project->getMilestones(),
                                fn($acc, MilestoneModel $milestone) => [
                                    ...$acc,
                                    ...$milestone->getPreceders(true)
                                ],
                                [],
                            ),
                        ],
                        fn(NodeModel $node) => $node->name,
                    ),
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
                Utils::getPath(Utils::getCriticalChain($project)),
                fn($acc, NodeModel $node) => [...$acc, $node->name],
                []
            ),
        ];
    }

    public function deserialize(array $volumes): ProjectModel
    {
        /* @var NodeModel[] $nodes */
        $nodes = array_reduce(
            [
                ...array_map(
                    fn(array $data) => new IssueModel($data['key'], $data['length'], [
                        'begin' => $data['begin'],
                        'end' => $data['end'],
                    ]),
                    $volumes[self::VOLUME_ISSUES],
                ),
                ...array_map(
                    fn(array $data) => new (
                        match($data['type']){
                            BufferModel::TYPE_PROJECT => ProjectBufferModel::class,
                            BufferModel::TYPE_MILESTONE => MilestoneBufferModel::class,
                            BufferModel::TYPE_FEEDING => FeedingBufferModel::class,
                        }
                    )($data['key'], $data['length'], [
                        'begin' => $data['begin'],
                        'end' => $data['end'],
                        'consumption' => $data['consumption'],
                    ]),
                    $volumes[self::VOLUME_BUFFERS],
                ),
                ...array_map(
                    fn(array $data) => new MilestoneModel($data['key'], [
                        'begin' => $data['begin'],
                        'end' => $data['end'],
                    ]),
                    $volumes[self::VOLUME_MILESTONES],
                ),
            ],
            fn(array $acc, NodeModel $node) => [...$acc, $node->name => $node],
            [],
        );

        $projectData = $volumes[self::VOLUME_PROJECT];
        $project = new ProjectModel($projectData['key'], [
            'begin' => $projectData['begin'],
            'end' => $projectData['end'],
        ]);
        foreach (
            array_filter(
                $nodes,
                fn(NodeModel $node) => $node instanceOf MilestoneModel,
            ) as $milestone
        ) {
            $project->addMilestone($milestone);
        }
        $nodes[$project->name] = $project;

        foreach ($volumes[self::VOLUME_LINKS] as $link) {
            $nodes[$link['from']]->precede($nodes[$link['to']], $link['type']);
        }

        return $project;
    }
}

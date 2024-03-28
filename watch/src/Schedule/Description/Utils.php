<?php

namespace Watch\Schedule\Description;

use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project;
use Watch\Schedule\Model\Buffer;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

class Utils
{
    /**
     * @param string $description
     * @param Mapper $mapper
     * @return Issue[]
     */
    public static function getIssues(string $description, Mapper $mapper): array
    {
        $projectEndDate = self::getProjectEndDate($description);
        $projectEndGap = self::getProjectEndGap($description);

        $issues = array_reduce(
            array_filter(
                array_map(
                    fn($line) => trim($line),
                    explode("\n", $description),
                ),
                fn($line) => strlen($line) > 0 && !str_contains($line, '^'),
            ),
            function($acc, $line) use ($mapper, $projectEndDate, $projectEndGap) {
                list($name, $duration, $started, $completed, $scheduled, $gap) = array_values(
                    self::getIssueComponents($line)
                );
                list($key, $type, $project, $milestone) = self::getNameComponents($name);
                $endGap = $gap - $projectEndGap;
                $beginGap = $endGap + $duration;
                return [
                    ...$acc,
                    $key => [
                        'key' => $key,
                        'summary' => $key,
                        'status' => $started
                            ? current($mapper->startedIssueStates)
                            : (
                            $completed
                                ? current($mapper->completedIssueStates)
                                : current($mapper->queuedIssueStates)
                            ),
                        'milestone' => $milestone,
                        'project' => $project,
                        'type' => $type,
                        'duration' => $duration,
                        'begin' => $scheduled ? $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                        'end' => $scheduled ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                    ],
                ];
            },
            []
        );

        return array_map(fn(array $issue) => new Issue(...$issue), array_values($issues));
    }

    /**
     * @param string $description
     * @param Mapper $mapper
     * @return Link[]
     */
    public static function getLinks(string $description, Mapper $mapper): array
    {
        return array_map(
            fn($link) => new Link(0, $link['from'], $link['to'], $link['type']),
            array_reduce(
                self::extractIssueLines($description),
                fn($acc, $line) => [
                    ...$acc,
                    ...self::getLinksByAttributes(self::getIssueKey($line), self::getIssueAttributes($line), $mapper),
                ],
                [],
            ),
        );
    }

    public static function getSchedule(string $description): array
    {
        $lines = [...array_filter(
            array_map(fn($line) => trim($line), explode("\n", $description)),
            fn($line) => strlen($line) > 0)
        ];

        $projectEndDate = self::getProjectEndDate($description);
        $projectEndGap = self::getProjectEndGap($description);

        $criticalChain = [];

        $schedule = array_reduce(
            array_filter($lines, fn($line) => !str_contains($line, '^') && !str_contains($line, '>')),
            function ($acc, $line) use ($projectEndDate, $projectEndGap, &$criticalChain)
            {
                $issueData = explode('|', $line);
                $ignored = str_ends_with($issueData[0], '-');
                $name = trim(rtrim($issueData[0], '-'));
                $length = strlen(trim($issueData[1]));
                $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
                $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
                $isCritical = in_array(trim($issueData[1])[0], ['x']);
                $isBuffer = in_array(trim($issueData[1])[0], ['_', '!']);
                $consumption = substr_count(trim($issueData[1]), '!');
                $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $projectEndGap;
                $beginGap = $endGap + $length;

                list($key, $type) = self::getNameComponents($name);

                if ($isIssue) {
                    $acc[Project::VOLUME_ISSUES][] = [
                        'key' => $key,
                        'length' => $length,
                        'begin' => $isScheduled
                            ? $ignored
                                ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                                : $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')
                            : null,
                        'end' => $isScheduled
                            ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                            : null,
                    ];
                    if ($isCritical) {
                        $criticalChain[$projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')] = $key;
                    }
                }

                if ($isBuffer) {
                    $acc[Project::VOLUME_BUFFERS][] = [
                        'key' => $key,
                        'length' => $length,
                        'type' => match($type) {
                            'PB' => Buffer::TYPE_PROJECT,
                            'MB' => Buffer::TYPE_MILESTONE,
                            'FB' => Buffer::TYPE_FEEDING,
                            default => '',
                        },
                        'begin' => $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d'),
                        'end' => $projectEndDate->modify("-{$endGap} day")->format('Y-m-d'),
                        'consumption' => $consumption,
                    ];
                }

                $acc[Project::VOLUME_LINKS] = [
                    ...$acc[Project::VOLUME_LINKS],
                    ...self::getLinksByAttributes($key, self::getIssueAttributes($line)),
                ];

                return $acc;
            },
            [
                Project::VOLUME_ISSUES => [],
                Project::VOLUME_BUFFERS => [],
                Project::VOLUME_LINKS => [],
            ]
        );

        $schedule[Project::VOLUME_PROJECT] = current(array_slice(self::getMilestones($description), -1));
        $schedule[Project::VOLUME_MILESTONES] = array_slice(self::getMilestones($description), 0, -1);

        krsort($criticalChain);
        $schedule[Project::VOLUME_CRITICAL_CHAIN] = array_values($criticalChain);

        return $schedule;
    }

    /**
     * @param string $description
     * @return array[]
     */
    public static function getMilestones(string $description): array
    {
        $milestones = array_map(
            fn($line) => [
                'key' => self::getMilestoneKey($line),
                'date' => self::getMilestoneDate($line),
            ],
            self::extractMilestoneLines($description),
        );
        usort($milestones, fn($a, $b) => $a['date'] < $b['date'] ? -1 : ($a['date'] > $b['date'] ? 1 : 0));

        $isEndMarkers = self::isEndMarkers($description);
        for ($i = 0; $i < sizeof($milestones); $i++) {
            $milestones[$i]['begin'] = ($isEndMarkers
                ? (
                    $i > 0
                        ? $milestones[$i - 1]['date']
                        : self::getProjectBeginDate($description)
                )
                : $milestones[$i]['date'])->format('Y-m-d');
            $milestones[$i]['end'] = ($isEndMarkers
                ? $milestones[$i]['date']
                : (
                    $i < sizeof($milestones) - 1
                        ? $milestones[$i + 1]['date']
                        : self::getProjectEndDate($description)
                ))->format('Y-m-d');
        }

        return array_map(
            fn($milestone) => array_filter(
                (array)$milestone,
                fn($key) => in_array($key, ['key', 'begin', 'end']),
                ARRAY_FILTER_USE_KEY,
            ),
            $milestones,
        );
    }

    /**
     * @param string $description
     * @return string[]
     */
    public static function getMilestoneNames(string $description): array
    {
        return array_slice(array_map(
            fn($milestone) => $milestone['key'],
            self::getMilestones($description)
        ), 0, -1);
    }

    public static function getProjectName(string $description): string
    {
        return current(array_reverse(array_map(
            fn($milestone) => $milestone['key'],
            self::getMilestones($description)
        )));
    }

    public static function getProjectBeginDate(string $description): \DateTimeImmutable|null
    {
        $projectLength = self::getProjectLength($description);
        return self::isEndMarkers($description)
            ? self::getProjectDate($description)?->modify("-{$projectLength} day")
            : self::getProjectDate($description);
    }

    public static function getProjectEndDate(string $description): \DateTimeImmutable|null
    {
        $projectLength = self::getProjectLength($description);
        return self::isEndMarkers($description)
            ? self::getProjectDate($description)
            : self::getProjectDate($description)?->modify("{$projectLength} day");
    }

    public static function getNowDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLines = self::extractMilestoneLines($description);
        if (empty($milestoneLines)) {
            return null;
        }
        $contextLine = self::extractContextLine($description);
        if (is_null($contextLine)) {
            return self::getProjectBeginDate($description);
        }
        $milestoneLine = current($milestoneLines);
        $gap = strpos($contextLine, '>') - strpos($milestoneLine, '^');
        return self::getMilestoneDate($milestoneLine)->modify("{$gap} day");
    }

    public static function getProjectLength(string $description): int
    {
        $tracks = self::extractTracks($description);
        return
            array_reduce(
                $tracks,
                fn($acc, $track) => max($acc, strlen($track)),
                0
            ) -
            array_reduce(
                $tracks,
                fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
                PHP_INT_MAX
            ) -
            array_reduce(
                $tracks,
                fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
                PHP_INT_MAX
            );
    }

    private static function getIssueComponents(string $line): array
    {
        $data = explode('|', $line);
        return [
            'name' => trim(rtrim($data[0], '~+')),
            'duration' => strlen(trim($data[1])),
            'started' => str_ends_with($data[0], '~'),
            'completed' => str_ends_with($data[0], '+'),
            'scheduled' => in_array(trim($data[1])[0], ['*']),
            'gap' => strlen($data[1]) - strlen(rtrim($data[1])),
        ];
    }

    private static function getNameComponents(string $name): array
    {
        return array_map(
            fn($name, $value) => $value ?? match($name) {
                'project' => 'PRJ',
                'type' => 'T',
                default => null,
            },
            ['key', 'type', 'project', 'milestone'],
            array_reverse(
                array_reduce(
                    explode('/', $name),
                    fn($acc, $name) => [
                        ...$acc,
                        ...array_reverse(explode('#', $name))
                    ],
                    [],
                ),
            ),
        );
    }

    private static function getIssueKey(string $line): string
    {
        return self::getKey($line, '|');
    }

    private static function getMilestoneKey(string $line): string
    {
        return self::getKey($line, '^');
    }

    private static function getKey(string $line, string $separator): string
    {
        list($key) = self::getNameComponents(
            trim(explode(' ', trim(explode($separator, $line)[0]))[0])
        );
        return $key;
    }

    /**
     * @param string $line
     * @return string[]
     */
    private static function getIssueAttributes(string $line): array
    {
        return self::getAttributes($line, '|');
    }

    private static function getMilestoneAttributes(string $line): array
    {
        return self::getAttributes($line, '^');
    }

    private static function getAttributes(string $line, string $separator): array
    {
        return array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', trim(array_reverse(explode($separator, $line))[0]))
            ),
            fn(string $attribute) => !empty($attribute),
        );
    }

    private static function getLinksByAttributes(string $from, array $attributes, Mapper $mapper = null): array
    {
        return array_reduce(
            array_map(
                fn(string $linkAttribute) => explode(' ', $linkAttribute),
                array_filter(
                    $attributes,
                    fn(string $attribute) => in_array($attribute[0], ['&', '@']),
                ),
            ),
            fn(array $acc, array $linkAttributeData) => [
                ...$acc,
                [
                    'from' => $from,
                    'to' => $linkAttributeData[1],
                    'type' => !is_null($mapper)
                        ? ($linkAttributeData[0] === '&' ? current($mapper->sequenceLinkTypes) : current($mapper->scheduleLnkTypes))
                        : ($linkAttributeData[0] === '&' ? 'sequence' : 'schedule'),
                ],
            ],
            [],
        );
    }

    private static function getProjectBeginGap(string $description): int
    {
        return array_reduce(
            self::extractTracks($description),
            fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
            PHP_INT_MAX
        );
    }

    private static function getProjectEndGap(string $description): int
    {
        return array_reduce(
            self::extractTracks($description),
            fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
            PHP_INT_MAX
        );
    }

    private static function getProjectDate(string $description): \DateTimeImmutable|null
    {
        return array_reduce(
            self::extractMilestoneLines($description),
            fn($acc, string $milestoneLine) => self::isEndMarkers($description)
                ? max($acc, self::getMilestoneDate($milestoneLine))
                : (
                    is_null($acc)
                        ? self::getMilestoneDate($milestoneLine)
                        : min($acc, self::getMilestoneDate($milestoneLine))
                ),
        );
    }

    private static function getMilestoneDate(string $milestoneLine): \DateTimeImmutable|null
    {
        return new \DateTimeImmutable(
            explode(
                ' ',
                array_reduce(
                    array_filter(
                        self::getMilestoneAttributes($milestoneLine),
                        fn($attribute) => str_starts_with($attribute, '#')
                    ),
                    fn($acc, $attribute) => $attribute
                )
            )[1]
        );
    }

    private static function isEndMarkers($description): bool
    {
        return
            array_reduce(
                self::extractMilestoneLines($description),
                fn($acc, $line) => max($acc, strrpos($line, '^')),
            ) >=
            array_reduce(
                array_map(
                    fn($line) => rtrim(substr($line, 0, strrpos($line, '|'))),
                    self::extractIssueLines($description)
                ),
                fn($acc, $line) => max($acc, strlen($line)),
            );
    }

    private static function extractIssueLines(string $description): array
    {
        return array_reduce(
            array_filter(
                array_map(fn($line) => $line, explode("\n", $description)),
                fn(string $line) => str_contains($line, '|')
            ),
            fn(array $acc, string $line) => [...$acc, $line],
        []);
    }

    private static function extractTracks(string $description): array
    {
        return array_map(
            fn(string $line) => explode('|', $line)[1],
            self::extractIssueLines($description),
        );
    }

    private static function extractMilestoneLines(string $description): array
    {
        return array_values(array_filter(
            array_map(fn($line) => $line, explode("\n", $description)),
            fn($line) => str_contains($line, '^')
        ));
    }

    private static function extractContextLine(string $description): string|null
    {
        return array_reduce(
            array_filter(
                array_map(fn($line) => $line, explode("\n", $description)),
                fn($line) => str_contains($line, '>')
            ),
            fn($acc, $line) => $line,
        );
    }
}

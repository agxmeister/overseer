<?php

namespace Watch\Schedule\Description;

use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Joint;

class Utils
{
    /**
     * @param string $description
     * @param callable|null $getAttributesByState
     * @return Issue[]
     */
    public static function getIssues(string $description, callable $getAttributesByState = null): array
    {
        $lines = [...array_filter(
            array_map(fn($line) => trim($line), explode("\n", $description)),
            fn($line) => strlen($line) > 0)
        ];

        $projectEndDate = self::getProjectEndDate($description);
        $projectEndGap = self::getProjectEndGap($description);

        $issues = array_reduce(
            array_filter($lines, fn($line) => !str_contains($line, '^')),
            function($issues, $line) use ($getAttributesByState, $projectEndDate, $projectEndGap, &$links) {
                $issueData = explode('|', $line);
                $started = str_ends_with($issueData[0], '~');
                $completed = str_ends_with($issueData[0], '+');
                $name = trim(rtrim($issueData[0], '~+'));
                $duration = strlen(trim($issueData[1]));
                $isScheduled = in_array(trim($issueData[1])[0], ['*']);
                $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $projectEndGap;
                $beginGap = $endGap + $duration;

                list($key, $type, $project) = array_map(
                    fn($name, $value) => $value ?? match($name) {
                        'project' => 'PRJ',
                        'type' => 'T',
                        default => null,
                    },
                    ['key', 'type', 'project'],
                    array_reverse(explode('/', $name)),
                );

                $issues[$key] = [
                    'key' => $key,
                    'summary' => $key,
                    'status' => $started ? 'In Progress' : ($completed ? 'Done' : 'To Do'),
                    'project' => $project,
                    'type' => $type,
                    'duration' => $duration,
                    'begin' => $isScheduled ? $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                    ...(!is_null($getAttributesByState)
                        ? $getAttributesByState($started, $completed)
                        : []
                    ),
                ];

                return $issues;
            },
            []
        );

        return array_map(fn(array $issue) => new Issue(...$issue), array_values($issues));
    }

    /**
     * @param string $description
     * @return Joint[]
     */
    public static function getJoints(string $description): array
    {
        $getIssueKey = function (string $line): string {
            $issueData = explode('|', $line);
            $name = trim(rtrim($issueData[0], '~+'));
            list($key, $type, $project) = array_map(
                fn($name, $value) => $value ?? match($name) {
                    'project' => 'PRJ',
                    'type' => 'T',
                    default => null,
                },
                ['key', 'type', 'project'],
                array_reverse(explode('/', $name)),
            );
            return $key;
        };

        $getIssueAttributes = function (string $line): string {
            $issueData = explode('|', $line);
            return trim($issueData[2]);
        };

        return array_map(
            fn($link) => new Joint(0, $link['from'], $link['to'], $link['type']),
            array_reduce(
                array_filter(
                    array_filter(
                        array_map(fn($line) => trim($line), explode("\n", $description)),
                        fn($line) => strlen($line) > 0
                    ),
                    fn($line) => !str_contains($line, '^'),
                ),
                fn($acc, $line) => [
                    ...$acc,
                    ...self::getLinks($getIssueKey($line), $getIssueAttributes($line), 'subject')
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

        $projectMilestoneName = self::getProjectMilestoneName($description);
        $projectEndDate = self::getProjectEndDate($description);
        $projectEndGap = self::getProjectEndGap($description);

        $criticalChain = [$projectEndDate->format('Y-m-d') => $projectMilestoneName];

        $schedule = array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^') && !str_contains($line, '>')), function ($schedule, $line) use ($projectEndDate, $projectEndGap, &$criticalChain) {
            $issueData = explode('|', $line);
            $ignored = str_ends_with($issueData[0], '-');
            $key = trim(rtrim($issueData[0], '-'));
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
            $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
            $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
            $isCritical = in_array(trim($issueData[1])[0], ['x']);
            $isBuffer = in_array(trim($issueData[1])[0], ['_', '!']);
            $consumption = substr_count(trim($issueData[1]), '!');
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $projectEndGap;
            $beginGap = $endGap + $duration;

            if ($isIssue) {
                $schedule['issues'][] = [
                    'key' => $key,
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
                $schedule['buffers'][] = [
                    'key' => $key,
                    'begin' => $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $projectEndDate->modify("-{$endGap} day")->format('Y-m-d'),
                    'consumption' => $consumption,
                ];
            }

            $schedule['links'] = [...$schedule['links'], ...self::getLinks($key, $attributes, 'schedule')];

            return $schedule;
        }, [
            'issues' => [],
            'buffers' => [],
            'links' => [],
        ]);

        $schedule['milestones'] = [[
            'key' => $projectMilestoneName,
            'begin' => self::getProjectBeginDate($description)->format('Y-m-d'),
            'end' => $projectEndDate->format('Y-m-d'),
        ]];

        krsort($criticalChain);
        $schedule['criticalChain'] = array_values($criticalChain);

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
                'key' => self::extractMilestoneName($line),
                'date' => self::extractMilestoneDate($line),
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
                ARRAY_FILTER_USE_KEY
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
        return array_map(
            fn($milestone) => $milestone['key'],
            self::getMilestones($description)
        );
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
        $contextLine = self::extractContextLine($description);
        if (empty($contextLine)) {
            return self::getProjectBeginDate($description);
        }
        return self::extractNowDate(
            $contextLine,
            self::extractMilestoneLines($description),
        );
    }

    public static function getProjectLength(string $description): int
    {
        $tracks = self::extractTracks($description);
        return array_reduce(
            $tracks,
            fn($acc, $track) => max($acc, strlen($track)),
            0
        ) - array_reduce(
            $tracks,
            fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
            PHP_INT_MAX
        ) - array_reduce(
            $tracks,
            fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
            PHP_INT_MAX
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
        $isEndMarkers = self::isEndMarkers($description);
        return array_reduce(
            self::extractMilestoneLines($description),
            fn($acc, string $milestoneLine) => $isEndMarkers
                ? max($acc, self::extractMilestoneDate($milestoneLine))
                : (
                    is_null($acc)
                        ? self::extractMilestoneDate($milestoneLine)
                        : min($acc, self::extractMilestoneDate($milestoneLine))
                ),
        );
    }

    private static function getProjectMilestoneName(string $description): string
    {
        return self::extractMilestoneName(array_reduce(
            self::extractMilestoneLines($description),
            fn($acc, $milestoneLine) => is_null($acc) || strpos($acc, '^') < strpos($milestoneLine, '^')
                ? $milestoneLine
                : $acc,
        ));
    }

    private static function isEndMarkers($description): bool
    {
        return array_reduce(
            self::extractMilestoneLines($description),
            fn($acc, $line) => max($acc, strrpos($line, '^')),
        ) >= array_reduce(
            array_map(
                fn($line) => rtrim(substr($line, 0, strrpos($line, '|'))),
                self::extractIssueLines($description)
            ),
            fn($acc, $line) => max($acc, strlen($line)),
        );
    }

    private static function extractMilestoneDate(string $milestoneLine): \DateTimeImmutable|null
    {
        if (empty($milestoneLine)) {
            return null;
        }
        $milestoneAttributes = trim(array_reverse(explode('^', $milestoneLine))[0]);
        $dateAttribute =
            array_reduce(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $milestoneAttributes)
                    ),
                    fn($attribute) => str_starts_with($attribute, '#')),
                fn($acc, $attribute) => $attribute
            );
        return new \DateTimeImmutable(explode(' ', $dateAttribute)[1] ?? '');
    }

    private static function extractMilestoneName(string $milestoneLine): string
    {
        list($milestoneName) = array_map(
            fn(string $milestoneLinePart) => trim($milestoneLinePart),
            explode('^', $milestoneLine),
        );
        return $milestoneName;
    }

    private static function extractNowDate(string $contextLine, array $milestoneLines): \DateTimeImmutable|null
    {
        if (empty($contextLine) || empty($milestoneLines)) {
            return null;
        }
        $milestoneLine = current($milestoneLines);
        $gap = (strpos($milestoneLine, '^') - strpos($contextLine, '>')) * -1;
        return self::extractMilestoneDate($milestoneLine)->modify("{$gap} day");
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

    private static function extractContextLine(string $description): string
    {
        return array_reduce(
            array_filter(
                array_map(fn($line) => $line, explode("\n", $description)),
                fn($line) => str_contains($line, '>')
            ),
            fn($acc, $line) => $line,
            '',
        );
    }

    private static function getLinks(string $from, string $attributes, string $model): array
    {
        $linkAttributes = array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', $attributes)
            ),
            fn($attribute) => $attribute && in_array($attribute[0], ['&', '@'])
        );
        $links = [];
        if (!empty($linkAttributes)) {
            foreach ($linkAttributes as $linkAttribute) {
                $linkData = explode(' ', $linkAttribute);
                $to = $linkData[1];
                $type = $model === 'subject'
                    ? ($linkData[0] === '&' ? 'Depends' : 'Follows')
                    : ($linkData[0] === '&' ? 'sequence' : 'schedule')
                ;
                $links[] = [
                    'from' => $from,
                    'to' => $to,
                    'type' => $type,
                ];
            }
        }
        return $links;
    }
}

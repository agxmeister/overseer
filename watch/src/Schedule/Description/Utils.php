<?php

namespace Watch\Schedule\Description;

use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

class Utils
{
    /**
     * @param string $description
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

        $links = [];
        $issues = array_reduce(
            array_filter($lines, fn($line) => !str_contains($line, '^')),
            function($issues, $line) use ($getAttributesByState, $projectEndDate, $projectEndGap, &$links) {
                $issueData = explode('|', $line);
                $started = str_ends_with($issueData[0], '~');
                $completed = str_ends_with($issueData[0], '+');
                $name = trim(rtrim($issueData[0], '~+'));
                $duration = strlen(trim($issueData[1]));
                $attributes = trim($issueData[2]);
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

                $links = [...$links, ...self::getLinks($key, $attributes, 'subject')];

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
                    'links' => [],
                ];

                return $issues;
            },
            []
        );

        foreach($links as $link) {
            $issues[$link['from']]['links'][] = new Link(
                0,
                $link['to'],
                $link['type'],
                Link::ROLE_INWARD,
            );
            $issues[$link['to']]['links'][] = new Link(
                0,
                $link['from'],
                $link['type'],
                Link::ROLE_OUTWARD,
            );
        }

        return array_map(fn(array $issue) => new Issue(...$issue), array_values($issues));
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
                    $criticalChain[$projectEndDate->modify("-{$endGap} day")->format('Y-m-d')] = $key;
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
     * @return string[]
     */
    public static function getMilestones(string $description): array
    {
        return array_map(
            fn($line) => self::extractMilestoneName($line),
            self::extractMilestoneLines($description),
        );
    }

    public static function getProjectBeginDate(string $description): \DateTimeImmutable|null
    {
        $projectBeginGap = self::getProjectBeginGap($description);
        $projectEndGap = self::getProjectEndGap($description);
        $projectLength = self::getProjectLength($description);
        return self::isEndMarkers($description)
            ? self::getProjectDate($description)
                ?->modify("-{$projectEndGap} day")
                ?->modify("-{$projectLength} day")
            : self::getProjectDate($description)
                ?->modify("{$projectBeginGap} day");
    }

    public static function getProjectEndDate(string $description): \DateTimeImmutable|null
    {
        $projectBeginGap = self::getProjectBeginGap($description);
        $projectEndGap = self::getProjectEndGap($description);
        $projectLength = self::getProjectLength($description);
        return self::isEndMarkers($description)
            ? self::getProjectDate($description)
                ?->modify("-{$projectEndGap} day")
            : self::getProjectDate($description)
                ?->modify("{$projectBeginGap} day")
                ?->modify("{$projectLength} day");
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
        $maxTrackLength = array_reduce(
            self::extractTracks($description),
            fn(int $acc, string $track) => max($acc, strlen($track)),
            0,
        );
        return $maxTrackLength - self::getProjectBeginGap($description) - self::getProjectEndGap($description);
    }

    private static function getProjectBeginGap(string $description): int
    {
        return self::getProjectGap($description, true);
    }

    private static function getProjectEndGap(string $description): int
    {
        return self::getProjectGap($description, false);
    }

    private static function getProjectGap(string $description, $isBegin): int
    {
        $tracks = self::extractTracks($description);
        $maxTrackLength = array_reduce(
            $tracks,
            fn(int $acc, string $track) => max($acc, strlen($track)),
            0,
        );
        $maxTrackLengthTrimmed = array_reduce(
            array_map(
                fn(string $track) => $isBegin ? ltrim($track) : rtrim($track),
                $tracks,
            ),
            fn(int $acc, string $line) => max($acc, strlen($line)),
            0,
        );
        return $maxTrackLength - $maxTrackLengthTrimmed;
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
        ) === array_reduce(
            self::extractIssueLines($description),
            fn($acc, $line) => max($acc, strrpos($line, '|')),
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

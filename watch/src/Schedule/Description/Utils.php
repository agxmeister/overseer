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

        $milestoneEndDate = self::getMilestoneEndDate($description);
        $milestoneEndGap = self::getProjectEndGap($description);

        $links = [];
        $issues = array_reduce(
            array_filter($lines, fn($line) => !str_contains($line, '^')),
            function($issues, $line) use ($getAttributesByState, $milestoneEndDate, $milestoneEndGap, &$links) {
                $issueData = explode('|', $line);
                $started = str_ends_with($issueData[0], '~');
                $completed = str_ends_with($issueData[0], '+');
                $name = trim(rtrim($issueData[0], '~+'));
                $duration = strlen(trim($issueData[1]));
                $attributes = trim($issueData[2]);
                $isScheduled = in_array(trim($issueData[1])[0], ['*']);
                $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $milestoneEndGap;
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
                    'begin' => $isScheduled ? $milestoneEndDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $milestoneEndDate->modify("-{$endGap} day")->format('Y-m-d') : null,
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

        list($milestoneName) = self::getMilestones($description);
        $milestoneEndDate = self::getMilestoneEndDate($description);
        $milestoneEndGap = self::getProjectEndGap($description);

        $criticalChain = [$milestoneEndDate->format('Y-m-d') => $milestoneName];

        $schedule = array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^') && !str_contains($line, '>')), function ($schedule, $line) use ($milestoneEndDate, $milestoneEndGap, &$criticalChain) {
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
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $milestoneEndGap;
            $beginGap = $endGap + $duration;

            if ($isIssue) {
                $schedule['issues'][] = [
                    'key' => $key,
                    'begin' => $isScheduled
                        ? $ignored
                            ? $milestoneEndDate->modify("-{$endGap} day")->format('Y-m-d')
                            : $milestoneEndDate->modify("-{$beginGap} day")->format('Y-m-d')
                        : null,
                    'end' => $isScheduled
                        ? $milestoneEndDate->modify("-{$endGap} day")->format('Y-m-d')
                        : null,
                ];
                if ($isCritical) {
                    $criticalChain[$milestoneEndDate->modify("-{$endGap} day")->format('Y-m-d')] = $key;
                }
            }

            if ($isBuffer) {
                $schedule['buffers'][] = [
                    'key' => $key,
                    'begin' => $milestoneEndDate->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $milestoneEndDate->modify("-{$endGap} day")->format('Y-m-d'),
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
            'key' => $milestoneName,
            'begin' => self::getMilestoneBeginDate($description)->format('Y-m-d'),
            'end' => $milestoneEndDate->format('Y-m-d'),
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

    public static function getMilestoneBeginDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLines = self::extractMilestoneLines($description);
        if (empty($milestoneLines)) {
            return null;
        }
        $milestoneLine = current($milestoneLines);
        $issueLines = self::extractIssueLines($description);
        $projectBeginGap = self::getProjectBeginGap($description);
        $projectEndGap = self::getProjectEndGap($description);
        $projectLength = self::getProjectLength($description);
        return self::isBeginMilestoneMarker($milestoneLine, $issueLines) ?
            self::getMilestoneDate($description)
                ?->modify("{$projectBeginGap} day") :
            self::getMilestoneDate($description)
                ?->modify("-{$projectEndGap} day")
                ?->modify("-{$projectLength} day");
    }

    public static function getMilestoneEndDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLines = self::extractMilestoneLines($description);
        if (empty($milestoneLines)) {
            return null;
        }
        $milestoneLine = current($milestoneLines);
        $issueLines = self::extractIssueLines($description);
        $projectBeginGap = self::getProjectBeginGap($description);
        $projectEndGap = self::getProjectEndGap($description);
        $projectLength = self::getProjectLength($description);
        return self::isBeginMilestoneMarker($milestoneLine, $issueLines) ?
            self::getMilestoneDate($description)
                ?->modify("{$projectBeginGap} day")
                ?->modify("{$projectLength} day") :
            self::getMilestoneDate($description)
                ?->modify("-{$projectEndGap} day");
    }

    public static function getNowDate(string $description): \DateTimeImmutable|null
    {
        $contextLine = self::extractContextLine($description);
        if (empty($contextLine)) {
            return self::getMilestoneBeginDate($description);
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

    private static function getMilestoneDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLines = self::extractMilestoneLines($description);
        if (empty($milestoneLines)) {
            return null;
        }
        $milestoneLine = current($milestoneLines);
        return self::extractMilestoneDate($milestoneLine);
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

    private static function isBeginMilestoneMarker(string $milestoneLine, array $issueLines): bool
    {
        return strpos($milestoneLine, '^') === array_reduce(
            $issueLines,
            fn($acc, $line) => max($acc, strpos($line, '|')),
        );
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

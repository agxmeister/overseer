<?php

namespace Tests\Support;

class Utils
{
    public static function getIssues(string $description): array
    {
        $lines = [...array_filter(
            array_map(fn($line) => trim($line), explode("\n", $description)),
            fn($line) => strlen($line) > 0)
        ];

        $milestoneDate = self::getMilestoneDate($description);

        $links = [];
        $issues = array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^')), function($issues, $line) use ($milestoneDate, &$links) {
            $issueData = explode('|', $line);
            $isCompleted = str_ends_with($issueData[0], '+');
            $key = trim(rtrim($issueData[0], '+'));
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
            $isScheduled = in_array(trim($issueData[1])[0], ['*']);
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;

            $links = [...$links, ...self::getLinks($key, $attributes)];

            $issues[$key] = [
                'key' => $key,
                'duration' => $duration,
                'begin' => $isScheduled ? $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                'end' => $isScheduled ? $milestoneDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                'isCompleted' => $isCompleted,
                'links' => [
                    'inward' => [],
                    'outward' => [],
                ]
            ];

            return $issues;
        }, []);

        foreach($links as $link) {
            $issues[$link['from']]['links']['inward'][] = [
                'key' => $link['to'],
                'type' => $link['type'],
            ];
            $issues[$link['to']]['links']['outward'][] = [
                'key' => $link['from'],
                'type' => $link['type'],
            ];
        }

        return array_values($issues);
    }

    public static function getSchedule(string $description): array
    {
        $lines = [...array_filter(
            array_map(fn($line) => trim($line), explode("\n", $description)),
            fn($line) => strlen($line) > 0)
        ];

        $milestoneDate = self::getMilestoneDate($description);

        $milestoneName = array_reduce(
            array_filter($lines, fn($line) => str_contains($line, '^')),
            fn($acc, $line) => trim(explode('^', $line)[0] ?? '')
        );

        $criticalChain = [$milestoneDate->format('Y-m-d') => $milestoneName];

        $schedule = array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^')), function ($schedule, $line) use ($milestoneDate, &$criticalChain) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
            $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
            $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
            $isCritical = in_array(trim($issueData[1])[0], ['x']);
            $isBuffer = in_array(trim($issueData[1])[0], ['_', '!']);
            $consumption = substr_count(trim($issueData[1]), '!');
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;

            if ($isIssue) {
                $schedule['issues'][] = [
                    'key' => $key,
                    'begin' => $isScheduled ? $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $milestoneDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                ];
                if ($isCritical) {
                    $criticalChain[$milestoneDate->modify("-{$endGap} day")->format('Y-m-d')] = $key;
                }
            }

            if ($isBuffer) {
                $schedule['buffers'][] = [
                    'key' => $key,
                    'begin' => $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $milestoneDate->modify("-{$endGap} day")->format('Y-m-d'),
                    'consumption' => $consumption,
                ];
            }

            $schedule['links'] = [...$schedule['links'], ...self::getLinks($key, $attributes)];

            return $schedule;
        }, [
            'issues' => [],
            'buffers' => [],
            'links' => [],
        ]);

        $schedule['milestones'] = [[
            'key' => $milestoneName,
            'begin' => array_reduce(
                $schedule['issues'],
                fn($acc, $issue) => min($acc, $issue['begin']),
                $milestoneDate->format('Y-m-d'),
            ),
            'end' => $milestoneDate->format('Y-m-d'),
        ]];

        krsort($criticalChain);
        $schedule['criticalChain'] = array_values($criticalChain);

        return $schedule;
    }

    public static function getMilestoneDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLine = self::extractMilestoneLine($description);
        return self::extractMilestoneDate($milestoneLine);
    }

    public static function getNowDate(string $description): \DateTimeImmutable|null
    {
        $milestoneLine = self::extractMilestoneLine($description);
        return self::extractNowDate($milestoneLine);
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

    private static function extractNowDate(string $milestoneLine): \DateTimeImmutable|null
    {
        if (empty($milestoneLine)) {
            return null;
        }
        $milestoneLineParts = array_reverse(explode('^', $milestoneLine));
        if (sizeof($milestoneLineParts) < 3) {
            return null;
        }
        $gap = strlen($milestoneLineParts[1]) + 1;
        return self::extractMilestoneDate($milestoneLine)->modify("- {$gap} day");
    }

    private static function extractMilestoneLine(string $description): string
    {
        return array_reduce(
            array_filter(
                array_filter(
                    array_map(fn($line) => trim($line), explode("\n", $description)),
                    fn($line) => strlen($line) > 0
                ),
                fn($line) => str_contains($line, '^')
            ),
            fn($acc, $line) => $line,
            '',
        );
    }

    private static function getLinks(string $from, string $attributes): array
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
                $type = $linkData[0] === '&' ? 'sequence' : 'schedule';
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

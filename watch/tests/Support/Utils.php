<?php

namespace Tests\Support;

class Utils
{
    public static function getIssues(string $description): array
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $milestoneDate = self::getMilestoneDate($lines);

        $links = [];
        $issues = array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^')), function($issues, $line) use ($milestoneDate, &$links) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
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
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $milestoneDate = self::getMilestoneDate($lines);

        return array_reduce(array_filter($lines, fn($line) => !str_contains($line, '^')), function ($schedule, $line) use ($milestoneDate) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
            $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
            $isCritical = in_array(trim($issueData[1])[0], ['!', 'x']);
            $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
            $isBuffer = in_array(trim($issueData[1])[0], ['_']);
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;

            if ($isIssue) {
                $schedule['issues'][] = [
                    'key' => $key,
                    'begin' => $isScheduled ? $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $milestoneDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                ];
            }

            if ($isBuffer) {
                $schedule['buffers'][] = [
                    'key' => $key,
                    'begin' => $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $milestoneDate->modify("-{$endGap} day")->format('Y-m-d'),
                ];
            }

            $schedule['links'] = [...$schedule['links'], ...self::getLinks($key, $attributes)];

            if ($isCritical) {
                $schedule['criticalChain'][] = $key;
            }

            return $schedule;
        }, [
            'issues' => [],
            'criticalChain' => [
                array_reduce(
                    array_filter($lines, fn($line) => str_contains($line, '^')),
                    fn($acc, $line) => trim(explode('^', $line)[0] ?? '')
                )
            ],
            'buffers' => [],
            'links' => [],
        ]);
    }

    private static function getMilestoneDate(array $lines): \DateTimeInterface
    {
        $milestoneAttributes = array_reduce(
            array_filter($lines, fn($line) => str_contains($line, '^')),
            fn($acc, $line) => trim(explode('^', $line)[1] ?? '')
        );
        if (is_null($milestoneAttributes)) {
            return new \DateTimeImmutable();
        }
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

    private static function getLinks(string $from, string $attributes): array
    {
        $linkAttributes = array_filter(array_map(fn($attribute) => trim($attribute), explode(',', $attributes)), fn($attribute) => $attribute && in_array($attribute[0], ['&', '@']));
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

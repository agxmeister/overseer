<?php

namespace Tests\Support;

class Utils
{
    public static function getIssues($description): array
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $links = [];
        $now = new \DateTimeImmutable('2023-10-30');
        foreach ($lines as $line) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $link = trim($issueData[2]);
            $isScheduled = trim($issueData[1])[0] === 'x';
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;
            $issues[$key] = [
                'key' => $key,
                'duration' => $duration,
                'begin' => $isScheduled ? $now->modify("-{$beginGap} day")->format('Y-m-d') : null,
                'end' => $isScheduled ? $now->modify("-{$endGap} day")->format('Y-m-d') : null,
                'links' => [
                    'inward' => [],
                    'outward' => [],
                ]
            ];
            if (!empty($link)) {
                $links[] = ['from' => $key, 'to' => $link];
            }
        }
        foreach($links as $link) {
            $issues[$link['from']]['links']['inward'][] = [
                'key' => $link['to'],
                'type' => 'sequence',
            ];
            $issues[$link['to']]['links']['outward'][] = [
                'key' => $link['from'],
                'type' => 'sequence',
            ];
        }

        return array_values($issues);
    }
}

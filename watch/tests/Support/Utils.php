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

    public static function getSchedule($description, $date)
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $criticalChain = [];
        $buffers = [];
        $links = [];
        $milestoneDate = new \DateTimeImmutable($date);
        $now = $milestoneDate->modify('+1 day');
        foreach ($lines as $line) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $linkString = trim($issueData[2]);
            if ($linkString) {
                $linkData = explode(' ', $linkString);
                $linkType = $linkData[0][0] === '-' ? 'sequence' : 'schedule';
                $link = $linkData[1];
            } else {
                $linkType = null;
                $link = null;
            }
            $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
            $isCritical = in_array(trim($issueData[1])[0], ['!', 'x']);
            $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
            $isBuffer = in_array(trim($issueData[1])[0], ['_']);
            $isMilestone = in_array(trim($issueData[1])[0], ['!']);
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;
            if ($isIssue) {
                $issues[] = [
                    'key' => $key,
                    'begin' => $isScheduled ? $now->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $now->modify("-{$endGap} day")->format('Y-m-d') : null,
                ];
            }
            if ($link) {
                $links[] = [
                    'from' => $key,
                    'to' => $link,
                    'type' => $linkType,
                ];
            }
            if ($isBuffer) {
                $buffers[] = [
                    'key' => $key,
                    'begin' => $now->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $now->modify("-{$endGap} day")->format('Y-m-d'),
                ];
            }
            if ($isCritical) {
                $criticalChain[] = $key;
            }
        }

        return [
            'issues' => $issues,
            'criticalChain' => $criticalChain,
            'buffers' => $buffers,
            'links' => $links,
        ];
    }
}

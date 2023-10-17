<?php

namespace Tests\Support;

class Utils
{
    public static function getIssues($description): array
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $links = [];
        foreach ($lines as $line) {
            $issueData = preg_split("/\s+/", $line);
            $key = $issueData[0];
            $duration = strlen($issueData[1]);
            $linkName = $issueData[2] ?? null;
            $issues[$key] = [
                'key' => $key,
                'duration' => $duration,
                'begin' => null,
                'end' => null,
                'links' => [
                    'inward' => [],
                    'outward' => [],
                ]
            ];
            if (!is_null($linkName)) {
                $links[] = ['from' => $key, 'to' => $linkName];
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
<?php

namespace Tests\Support;

class Utils
{
    public static function getIssues($description): array
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $links = [];

        $milestoneDate = self::getMilestoneDate($lines);

        foreach ($lines as $line) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
            $isScheduled = in_array(trim($issueData[1])[0], ['*']);
            $isMilestone = in_array(trim($issueData[1])[0], ['!']);
            $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) + 1;
            $beginGap = $endGap + $duration - 1;

            if ($isMilestone) {
                continue;
            }

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
        }

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

    public static function getSchedule($description)
    {
        $lines = [...array_filter(array_map(fn($line) => trim($line), explode("\n", $description)), fn($line) => strlen($line) > 0)];

        $issues = [];
        $criticalChain = [];
        $buffers = [];
        $links = [];

        $milestoneDate = self::getMilestoneDate($lines);

        foreach ($lines as $line) {
            $issueData = explode('|', $line);
            $key = trim($issueData[0]);
            $duration = strlen(trim($issueData[1]));
            $attributes = trim($issueData[2]);
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
                    'begin' => $isScheduled ? $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                    'end' => $isScheduled ? $milestoneDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                ];
            }

            if ($isBuffer) {
                $buffers[] = [
                    'key' => $key,
                    'begin' => $milestoneDate->modify("-{$beginGap} day")->format('Y-m-d'),
                    'end' => $milestoneDate->modify("-{$endGap} day")->format('Y-m-d'),
                ];
            }

            $links = [...$links, ...self::getLinks($key, $attributes)];

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

    private static function getMilestoneDate(array $lines): \DateTimeInterface
    {
        $milestoneAttributes = array_reduce($lines, function ($acc, $line) {
            $data = explode('|', $line);
            return trim($data[1])[0] === '!' ? trim($data[2]) : $acc;
        });
        if (is_null($milestoneAttributes)) {
            return new \DateTimeImmutable();
        }
        $dateAttributes = array_filter(array_map(fn($attribute) => trim($attribute), explode(',', $milestoneAttributes)), fn($attribute) => $attribute[0] === '#');
        $dateAttribute = reset($dateAttributes);
        $dataData = explode(' ', $dateAttribute);
        $date = $dataData[1] ?? '';
        $milestoneDate = new \DateTimeImmutable($date);
        return $milestoneDate->modify('+1 day');
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

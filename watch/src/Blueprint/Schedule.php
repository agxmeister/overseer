<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Line\BufferLine;
use Watch\Blueprint\Line\ContextLine;
use Watch\Blueprint\Line\Line;
use Watch\Blueprint\Line\MilestoneLine;
use Watch\Blueprint\Line\Schedule\IssueLine;
use Watch\Blueprint\Line\TrackLine;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Serializer\Project;

class Schedule extends Blueprint
{
    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function getSchedule(): array
    {
        $projectEndDate = $this->getProjectEndDate();
        $projectEndGap = $this->getProjectEndGap();

        $criticalChain = [];

        $schedule = array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof TrackLine
            ),
            function ($acc, TrackLine $line) use ($projectEndDate, $projectEndGap, &$criticalChain) {
                $endGap = $line->track->gap - $projectEndGap;
                $beginGap = $endGap + $line->track->duration;

                if ($line instanceof IssueLine) {
                    $acc[Project::VOLUME_ISSUES][] = [
                        'key' => $line->key,
                        'length' => $line->track->duration,
                        'begin' => $line->scheduled
                            ? $line->ignored
                                ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                                : $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')
                            : null,
                        'end' => $line->scheduled
                            ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                            : null,
                    ];
                    if ($line->critical) {
                        $criticalChain[$projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')] = $line->key;
                    }
                }

                if ($line instanceof BufferLine) {
                    $acc[Project::VOLUME_BUFFERS][] = [
                        'key' => $line->key,
                        'length' => $line->track->duration,
                        'type' => match($line->type) {
                            'PB' => Buffer::TYPE_PROJECT,
                            'MB' => Buffer::TYPE_MILESTONE,
                            'FB' => Buffer::TYPE_FEEDING,
                            default => '',
                        },
                        'begin' => $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d'),
                        'end' => $projectEndDate->modify("-{$endGap} day")->format('Y-m-d'),
                        'consumption' => $line->consumption,
                    ];
                }

                $acc[Project::VOLUME_LINKS] = [
                    ...$acc[Project::VOLUME_LINKS],
                    ...$line->getLinks(),
                ];

                return $acc;
            },
            [
                Project::VOLUME_ISSUES => [],
                Project::VOLUME_BUFFERS => [],
                Project::VOLUME_LINKS => [],
            ]
        );

        $schedule[Project::VOLUME_PROJECT] = current(array_slice($this->getMilestones(), -1));
        $schedule[Project::VOLUME_MILESTONES] = array_slice($this->getMilestones(), 0, -1);

        krsort($criticalChain);
        $schedule[Project::VOLUME_CRITICAL_CHAIN] = array_values($criticalChain);

        return $schedule;
    }

    protected function getLine(string $content): Line|null
    {
        $offsets = [];
        $issueLineProperties = Utils::getStringParts($content, self::PATTERN_ISSUE_LINE, $offsets, project: 'PRJ', type: 'T');
        if (!is_null($issueLineProperties)) {
            list(
                'key' => $key,
                'type' => $type,
                'project' => $project,
                'milestone' => $milestone,
                'modifier' => $modifier,
                'track' => $track,
                'attributes' => $attributes,
            ) = $issueLineProperties;
            list('endMarker' => $endMarkerOffset) = $offsets;
            return new IssueLine(
                $key,
                $type,
                $project,
                $milestone,
                $modifier,
                $track,
                $attributes,
                $endMarkerOffset,
            );
        }

        $offsets = [];
        $milestoneLineProperties = Utils::getStringParts($content, self::PATTERN_MILESTONE_LINE, $offsets, key: 'PRJ');
        if (!is_null($milestoneLineProperties)) {
            list(
                'key' => $key,
                'attributes' => $attributes
            ) = $milestoneLineProperties;
            list('marker' => $markerOffset) = $offsets;
            return new MilestoneLine($key, $attributes, $markerOffset);
        }

        $offsets = [];
        $bufferLineProperties = Utils::getStringParts($content, self::PATTERN_BUFFER_LINE, $offsets, type: 'T');
        if (!is_null($bufferLineProperties)) {
            list(
                'key' => $key,
                'type' => $type,
                'track' => $track,
                'attributes' => $attributes,
            ) = $bufferLineProperties;
            list('endMarker' => $endMarkerOffset) = $offsets;
            return new BufferLine($key, $type, $track, $attributes, $endMarkerOffset);
        }

        $offsets = [];
        $contextLineProperties = Utils::getStringParts($content, self::PATTERN_CONTEXT_LINE, $offsets);
        if (!is_null($contextLineProperties)) {
            list('marker' => $markerOffset) = $offsets;
            return new ContextLine($markerOffset);
        }

        return null;
    }
}
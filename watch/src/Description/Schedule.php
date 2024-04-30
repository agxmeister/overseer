<?php

namespace Watch\Description;

use Watch\Description;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Serializer\Project;

class Schedule extends Description
{
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+\^\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+\|(?<track>[_!\s]*)\|\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/>/';

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

                if ($line instanceof ScheduleIssueLine) {
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

    protected function getLine(string $content): Line
    {
        $milestoneLineProperties = Utils::getStringParts($content, self::PATTERN_MILESTONE_LINE, key: 'PRJ');
        if (!is_null($milestoneLineProperties)) {
            return new MilestoneLine($content, ...$milestoneLineProperties);
        }

        $bufferLineProperties = Utils::getStringParts($content, self::PATTERN_BUFFER_LINE, type: 'T');
        if (!is_null($bufferLineProperties)) {
            return new BufferLine($content, ...$bufferLineProperties);
        }

        $contextLineProperties = Utils::getStringParts($content, self::PATTERN_CONTEXT_LINE);
        if (!is_null($contextLineProperties)) {
            return new ContextLine($content);
        }

        return match (1) {
            preg_match(ScheduleIssueLine::PATTERN, $content) => new ScheduleIssueLine($content),
            default => null,
        };
    }
}

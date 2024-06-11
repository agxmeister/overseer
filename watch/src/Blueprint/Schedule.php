<?php

namespace Watch\Blueprint;

use DateTimeImmutable;
use Watch\Blueprint\Model\Schedule\Buffer;
use Watch\Blueprint\Model\Schedule\Issue;
use Watch\Schedule\Model\Buffer as ScheduleBuffer;
use Watch\Schedule\Serializer\Project;

readonly class Schedule
{
    use HasProject;

    public function __construct(public array $issues, public array $buffers, public array $milestones, public ?DateTimeImmutable $nowDate, public bool $isEndMarkers)
    {
    }

    public function getSchedule(): array
    {
        $projectEndDate = $this->getProjectEndDate();
        $projectEndGap = $this->getProjectEndGap();

        $criticalChain = [];

        $schedule = array_reduce(
            [...$this->issues, ...$this->buffers],
            function ($acc, $line) use ($projectEndDate, $projectEndGap, &$criticalChain) {
                $endGap = $line->track->gap - $projectEndGap;
                $beginGap = $endGap + $line->track->duration;

                if ($line instanceof Issue) {
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

                if ($line instanceof Buffer) {
                    $acc[Project::VOLUME_BUFFERS][] = [
                        'key' => $line->key,
                        'length' => $line->track->duration,
                        'type' => match($line->type) {
                            'PB' => ScheduleBuffer::TYPE_PROJECT,
                            'MB' => ScheduleBuffer::TYPE_MILESTONE,
                            'FB' => ScheduleBuffer::TYPE_FEEDING,
                            default => '',
                        },
                        'begin' => $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d'),
                        'end' => $projectEndDate->modify("-{$endGap} day")->format('Y-m-d'),
                        'consumption' => $line->consumption,
                    ];
                }

                $acc[Project::VOLUME_LINKS] = [
                    ...$acc[Project::VOLUME_LINKS],
                    ...$line->links,
                ];

                return $acc;
            },
            [
                Project::VOLUME_ISSUES => [],
                Project::VOLUME_BUFFERS => [],
                Project::VOLUME_LINKS => [],
            ]
        );

        $schedule[Project::VOLUME_PROJECT] = current(array_slice($this->getMilestonesData(), -1));
        $schedule[Project::VOLUME_MILESTONES] = array_slice($this->getMilestonesData(), 0, -1);

        krsort($criticalChain);
        $schedule[Project::VOLUME_CRITICAL_CHAIN] = array_values($criticalChain);

        return $schedule;
    }

    protected function getModelsWithTracks(): array
    {
        return [...$this->issues, ...$this->buffers];
    }
}

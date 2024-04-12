<?php

namespace Watch\Description;

use Watch\Description;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Serializer\Project;

class Schedule extends Description
{
    public function getSchedule(): array
    {
        $projectEndDate = $this->getProjectEndDate();
        $projectEndGap = $this->getProjectEndGap();

        $criticalChain = [];

        $schedule = array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof IssueLine
            ),
            function ($acc, IssueLine $line) use ($projectEndDate, $projectEndGap, &$criticalChain) {
                $issueData = explode('|', $line);
                $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
                $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
                $isCritical = in_array(trim($issueData[1])[0], ['x']);
                $isBuffer = in_array(trim($issueData[1])[0], ['_', '!']);
                $consumption = substr_count(trim($issueData[1]), '!');
                $endGap = $line->track->gap - $projectEndGap;
                $beginGap = $endGap + $line->track->duration;

                if ($isIssue) {
                    $acc[Project::VOLUME_ISSUES][] = [
                        'key' => $line->key,
                        'length' => $line->track->duration,
                        'begin' => $isScheduled
                            ? $line->ignored
                                ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                                : $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')
                            : null,
                        'end' => $isScheduled
                            ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                            : null,
                    ];
                    if ($isCritical) {
                        $criticalChain[$projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')] = $line->key;
                    }
                }

                if ($isBuffer) {
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
                        'consumption' => $consumption,
                    ];
                }

                $acc[Project::VOLUME_LINKS] = [
                    ...$acc[Project::VOLUME_LINKS],
                    ...$this->getLinksByAttributes($line->key, $line->attributes),
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
}

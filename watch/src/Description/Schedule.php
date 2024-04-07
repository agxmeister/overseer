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
                $ignored = str_ends_with($issueData[0], '-');
                $length = strlen(trim($issueData[1]));
                $isScheduled = in_array(trim($issueData[1])[0], ['x', '*', '_']);
                $isIssue = in_array(trim($issueData[1])[0], ['x', '*', '.']);
                $isCritical = in_array(trim($issueData[1])[0], ['x']);
                $isBuffer = in_array(trim($issueData[1])[0], ['_', '!']);
                $consumption = substr_count(trim($issueData[1]), '!');
                $endGap = strlen($issueData[1]) - strlen(rtrim($issueData[1])) - $projectEndGap;
                $beginGap = $endGap + $length;

                list($key, $type) = $this->getNameComponents($line->name, ['key', 'type']);

                if ($isIssue) {
                    $acc[Project::VOLUME_ISSUES][] = [
                        'key' => $key,
                        'length' => $length,
                        'begin' => $isScheduled
                            ? $ignored
                                ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                                : $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')
                            : null,
                        'end' => $isScheduled
                            ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d')
                            : null,
                    ];
                    if ($isCritical) {
                        $criticalChain[$projectEndDate->modify("-{$beginGap} day")->format('Y-m-d')] = $key;
                    }
                }

                if ($isBuffer) {
                    $acc[Project::VOLUME_BUFFERS][] = [
                        'key' => $key,
                        'length' => $length,
                        'type' => match($type) {
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
                    ...$this->getLinksByAttributes($key, $line->getAttributes()),
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

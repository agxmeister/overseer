<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Subject\IssueLine;
use Watch\Blueprint\Model\WithTrack;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class Subject extends Blueprint
{
    /**
     * @param Mapper $mapper
     * @return Issue[]
     */
    public function getIssues(Mapper $mapper): array
    {
        $projectEndDate = $this->getProjectEndDate();
        $projectEndGap = $this->getProjectEndGap();

        $issues = array_reduce(
            array_filter(
                $this->lines,
                fn($line) => $line instanceof IssueLine
            ),
            function($acc, IssueLine $line) use ($mapper, $projectEndDate, $projectEndGap) {
                $endGap = $line->track->gap - $projectEndGap;
                $beginGap = $endGap + $line->track->duration;
                return [
                    ...$acc,
                    $line->key => [
                        'key' => $line->key,
                        'summary' => $line->key,
                        'status' => $line->started
                            ? current($mapper->startedIssueStates)
                            : (
                            $line->completed
                                ? current($mapper->completedIssueStates)
                                : current($mapper->queuedIssueStates)
                            ),
                        'milestone' => $line->milestone,
                        'project' => $line->project,
                        'type' => $line->type,
                        'duration' => $line->track->duration,
                        'begin' => $line->scheduled ? $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                        'end' => $line->scheduled ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                    ],
                ];
            },
            []
        );

        return array_map(fn(array $issue) => new Issue(...$issue), array_values($issues));
    }

    public function getLinks(): array
    {
        return array_map(
            fn($link) => new Link(0, $link['from'], $link['to'], $link['type']),
            array_reduce(
                $this->getTrackLines(),
                fn($acc, WithTrack $line) => [
                    ...$acc,
                    ...$line->links,
                ],
                [],
            ),
        );
    }
}

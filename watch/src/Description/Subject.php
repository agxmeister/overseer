<?php

namespace Watch\Description;

use Watch\Description;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class Subject extends Description
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
                array_map(
                    fn($line) => trim($line),
                    explode("\n", $this->description),
                ),
                fn($line) => strlen($line) > 0 && !str_contains($line, '^'),
            ),
            function($acc, $line) use ($mapper, $projectEndDate, $projectEndGap) {
                list($name, $duration, $started, $completed, $scheduled, $gap) = array_values(
                    $this->getIssueComponents($line)
                );
                list($key, $type, $project, $milestone) = $this->getNameComponents($name);
                $endGap = $gap - $projectEndGap;
                $beginGap = $endGap + $duration;
                return [
                    ...$acc,
                    $key => [
                        'key' => $key,
                        'summary' => $key,
                        'status' => $started
                            ? current($mapper->startedIssueStates)
                            : (
                            $completed
                                ? current($mapper->completedIssueStates)
                                : current($mapper->queuedIssueStates)
                            ),
                        'milestone' => $milestone,
                        'project' => $project,
                        'type' => $type,
                        'duration' => $duration,
                        'begin' => $scheduled ? $projectEndDate->modify("-{$beginGap} day")->format('Y-m-d') : null,
                        'end' => $scheduled ? $projectEndDate->modify("-{$endGap} day")->format('Y-m-d') : null,
                    ],
                ];
            },
            []
        );

        return array_map(fn(array $issue) => new Issue(...$issue), array_values($issues));
    }

    /**
     * @param Mapper $mapper
     * @return Link[]
     */
    public function getLinks(Mapper $mapper): array
    {
        return array_map(
            fn($link) => new Link(0, $link['from'], $link['to'], $link['type']),
            array_reduce(
                $this->extractIssueLines(),
                fn($acc, $line) => [
                    ...$acc,
                    ...$this->getLinksByAttributes($this->getIssueKey($line), $this->getIssueAttributes($line), $mapper),
                ],
                [],
            ),
        );
    }
}

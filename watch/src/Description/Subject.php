<?php

namespace Watch\Description;

use Watch\Description;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

class Subject extends Description
{
    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

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
                $this->getLines(),
                fn(Line $line) => $line instanceof SubjectIssueLine
            ),
            function($acc, SubjectIssueLine $line) use ($mapper, $projectEndDate, $projectEndGap) {
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

    /**
     * @param Mapper $mapper
     * @return Link[]
     */
    public function getLinks(Mapper $mapper): array
    {
        return array_map(
            fn($link) => new Link(0, $link['from'], $link['to'], $link['type']),
            array_reduce(
                $this->getTrackLines(),
                fn($acc, TrackLine $line) => [
                    ...$acc,
                    ...$this->getSubjectLinksByAttributes($line->key, $line->attributes, $mapper),
                ],
                [],
            ),
        );
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
            return new SubjectIssueLine(
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
            list('key' => $key, 'attributes' => $attributes) = $milestoneLineProperties;
            list('marker' => $markerOffset) = $offsets;
            return new MilestoneLine($key, $attributes, $markerOffset);
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

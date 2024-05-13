<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Line\ContextLine;
use Watch\Blueprint\Line\Line;
use Watch\Blueprint\Line\MilestoneLine;
use Watch\Blueprint\Line\Subject\IssueLine;
use Watch\Blueprint\Subject as SubjectBlueprintModel;
use Watch\Blueprint\Utils;

readonly class Subject extends Blueprint
{
    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function create(string $content): SubjectBlueprintModel
    {
        return new SubjectBlueprintModel($this->getLines($content));
    }

    protected function getLine(string $content): ?Line
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
                $this->getTrack($track),
                $this->getLineAttributes($attributes),
                $endMarkerOffset,
            );
        }

        $offsets = [];
        $milestoneLineProperties = Utils::getStringParts($content, self::PATTERN_MILESTONE_LINE, $offsets, key: 'PRJ');
        if (!is_null($milestoneLineProperties)) {
            list('key' => $key, 'attributes' => $attributes) = $milestoneLineProperties;
            list('marker' => $markerOffset) = $offsets;
            return new MilestoneLine($key, $this->getLineAttributes($attributes), $markerOffset);
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

<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Model\ContextLine;
use Watch\Blueprint\Model\Model;
use Watch\Blueprint\Model\Schedule\BufferLine;
use Watch\Blueprint\Model\Schedule\IssueLine;
use Watch\Blueprint\Model\Schedule\MilestoneLine;
use Watch\Blueprint\Schedule as ScheduleBlueprintModel;
use Watch\Blueprint\Utils;

readonly class Schedule extends Blueprint
{
    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function create(string $content): ScheduleBlueprintModel
    {
        $context = new Context();
        $lines = $this->getLines($content, $context);

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $lines,
            fn($acc, Model $line) => $line instanceof MilestoneLine ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new ScheduleBlueprintModel($lines, $nowDate, $isEndMarkers);
    }

    protected function getLine(string $content, Context &$context): ?Model
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
            $trackGap = strlen($track) - strlen(rtrim($track));
            $context->setIssuesEndPosition($endMarkerOffset - $trackGap);
            return new IssueLine(
                $key,
                $type,
                $project,
                $milestone,
                $this->getTrack($track),
                $this->getLineAttributes($attributes),
                $modifier === '~',
                $modifier === '+',
                str_contains($track, '*') || str_contains($track, 'x'),
                str_contains($track, 'x'),
                $modifier === '-',
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
            $context->setProjectMarkerOffset($markerOffset);
            return new MilestoneLine($key, $this->getLineAttributes($attributes));
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
            $trackGap = strlen($track) - strlen(rtrim($track));
            $context->setIssuesEndPosition($endMarkerOffset - $trackGap);
            return new BufferLine(
                $key,
                $type,
                $this->getTrack($track),
                $this->getLineAttributes($attributes),
            );
        }

        $offsets = [];
        $contextLineProperties = Utils::getStringParts($content, self::PATTERN_CONTEXT_LINE, $offsets);
        if (!is_null($contextLineProperties)) {
            list('marker' => $markerOffset) = $offsets;
            $context->setContextMarkerOffset($markerOffset);
            return new ContextLine();
        }

        return null;
    }
}

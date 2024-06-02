<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Schedule\Buffer;
use Watch\Blueprint\Model\Schedule\Issue;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Schedule as ScheduleBlueprintModel;
use Watch\Blueprint\Utils;

readonly class Schedule
{
    use HasLines;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function create(string $content): ScheduleBlueprintModel
    {
        $context = new Context();

        $lines = $this->getLines($content);

        $issueModels = array_map(
            fn($line) => $this->getIssueModel($line, $context),
            array_filter(
                $lines,
                fn($line) => preg_match(self::PATTERN_ISSUE_LINE, $line),
            ),
        );

        $bufferModels = array_map(
            fn($line) => $this->getBufferModel($line, $context),
            array_filter(
                $lines,
                fn($line) => preg_match(self::PATTERN_BUFFER_LINE, $line),
            ),
        );

        $milestoneModels = array_map(
            fn($line) => $this->getMilestoneModel($line, $context),
            array_filter(
                $lines,
                fn($line) => preg_match(self::PATTERN_MILESTONE_LINE, $line),
            ),
        );

        $contextLine = array_reduce(
            array_filter(
                $lines,
                fn($line) => preg_match(self::PATTERN_CONTEXT_LINE, $line),
            ),
            fn($acc, $line) => $line,
        );
        if (!is_null($contextLine)) {
            $offsets = [];
            Utils::getStringParts($contextLine, self::PATTERN_CONTEXT_LINE, $offsets);
            list('marker' => $markerOffset) = $offsets;
            $context->setContextMarkerOffset($markerOffset);
        }

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new ScheduleBlueprintModel($issueModels, $bufferModels, $milestoneModels, $nowDate, $isEndMarkers);
    }

    private function getIssueModel($line, Context &$context): Issue
    {
        $offsets = [];
        $issueLineProperties = Utils::getStringParts($line, self::PATTERN_ISSUE_LINE, $offsets, project: 'PRJ', type: 'T');
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
        $lineAttributes = $this->getLineAttributes($attributes);
        $lineLinks = $this->getLineLinks($key, $lineAttributes);
        return new Issue(
            $key,
            $type,
            $project,
            $milestone,
            new Track($track),
            $lineLinks,
            $lineAttributes,
            $modifier === '~',
            $modifier === '+',
            str_contains($track, '*') || str_contains($track, 'x'),
            str_contains($track, 'x'),
            $modifier === '-',
        );
    }

    private function getBufferModel($line, Context &$context): Buffer
    {
        $offsets = [];
        $bufferLineProperties = Utils::getStringParts($line, self::PATTERN_BUFFER_LINE, $offsets, type: 'T');
        list(
            'key' => $key,
            'type' => $type,
            'track' => $track,
            'attributes' => $attributes,
            ) = $bufferLineProperties;
        list('endMarker' => $endMarkerOffset) = $offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $context->setIssuesEndPosition($endMarkerOffset - $trackGap);
        $lineAttributes = $this->getLineAttributes($attributes);
        $lineLinks = $this->getLineLinks($key, $lineAttributes);
        $consumption = substr_count(trim($track), '!');
        return new Buffer(
            $key,
            $type,
            new Track($track),
            $lineLinks,
            $lineAttributes,
            $consumption,
        );
    }

    private function getMilestoneModel($line, Context &$context): Milestone
    {
        $offsets = [];
        $milestoneLineProperties = Utils::getStringParts($line, self::PATTERN_MILESTONE_LINE, $offsets, key: 'PRJ');
        list(
            'key' => $key,
            'attributes' => $attributes
            ) = $milestoneLineProperties;
        list('marker' => $markerOffset) = $offsets;
        $context->setProjectMarkerOffset($markerOffset);
        return new Milestone($key, $this->getLineAttributes($attributes));
    }

    protected function getLineLinks(string $key, array $attributes): array
    {
        return array_reduce(
            array_filter(
                $attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $key,
                    'to' => $attribute->value,
                    'type' => $attribute->type === AttributeType::Sequence ? 'sequence' : 'schedule',
                ],
            ],
            [],
        );
    }
}

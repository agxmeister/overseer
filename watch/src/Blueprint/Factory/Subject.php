<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Subject\Issue;
use Watch\Blueprint\Subject as SubjectBlueprintModel;
use Watch\Blueprint\Utils;
use Watch\Schedule\Mapper;

readonly class Subject extends Blueprint
{
    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function __construct(private Mapper $mapper)
    {
    }

    public function create(string $content): SubjectBlueprintModel
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

        return new SubjectBlueprintModel($issueModels, $milestoneModels, $nowDate, $isEndMarkers);
    }

    private function getIssueModel(string $content, Context &$context): Issue
    {
        $offsets = [];
        $issueLineProperties = Utils::getStringParts($content, self::PATTERN_ISSUE_LINE, $offsets, project: 'PRJ', type: 'T');
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
            $this->getTrack($track),
            $lineLinks,
            $lineAttributes,
            $modifier === '~',
            $modifier === '+',
            str_contains($track, '*'),
        );
    }

    private function getMilestoneModel(string $content, Context &$context): Milestone
    {
        $offsets = [];
        $milestoneLineProperties = Utils::getStringParts($content, self::PATTERN_MILESTONE_LINE, $offsets, key: 'PRJ');
        list('key' => $key, 'attributes' => $attributes) = $milestoneLineProperties;
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
                    'type' => $attribute->type === AttributeType::Sequence
                        ? current($this->mapper->sequenceLinkTypes)
                        : current($this->mapper->scheduleLnkTypes),
                ],
            ],
            [],
        );
    }
}

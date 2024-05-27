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
        $lines = $this->getModels($content, $context);

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $lines,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new SubjectBlueprintModel($lines, $nowDate, $isEndMarkers);
    }

    protected function getModel(string $content, Context &$context): mixed
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

        $offsets = [];
        $milestoneLineProperties = Utils::getStringParts($content, self::PATTERN_MILESTONE_LINE, $offsets, key: 'PRJ');
        if (!is_null($milestoneLineProperties)) {
            list('key' => $key, 'attributes' => $attributes) = $milestoneLineProperties;
            list('marker' => $markerOffset) = $offsets;
            $context->setProjectMarkerOffset($markerOffset);
            return new Milestone($key, $this->getLineAttributes($attributes));
        }

        $offsets = [];
        $contextLineProperties = Utils::getStringParts($content, self::PATTERN_CONTEXT_LINE, $offsets);
        if (!is_null($contextLineProperties)) {
            list('marker' => $markerOffset) = $offsets;
            $context->setContextMarkerOffset($markerOffset);
            return null;
        }

        return null;
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

<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Subject\Issue;
use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Subject as SubjectBlueprintModel;
use Watch\Schedule\Mapper;

readonly class Subject
{
    use HasContext;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function __construct(private Mapper $mapper)
    {
    }

    public function create(string $content): SubjectBlueprintModel
    {
        $context = $this->getContext($content, self::PATTERN_CONTEXT_LINE);

        $issueModels = array_map(
            fn($line) => $this->getIssueModel(
                new Line($line, self::PATTERN_ISSUE_LINE, project: 'PRJ', type: 'T'),
                $context,
            ),
            array_filter(
                $context->lines,
                fn($line) => preg_match(self::PATTERN_ISSUE_LINE, $line),
            ),
        );

        $milestoneModels = array_map(
            fn($line) => $this->getMilestoneModel(
                new Line($line, self::PATTERN_MILESTONE_LINE, key: 'PRJ'),
                $context,
            ),
            array_filter(
                $context->lines,
                fn($line) => preg_match(self::PATTERN_MILESTONE_LINE, $line),
            ),
        );

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new SubjectBlueprintModel($issueModels, $milestoneModels, $nowDate, $isEndMarkers);
    }

    private function getIssueModel(Line $line, Context &$context): Issue
    {
        list(
            'key' => $key,
            'type' => $type,
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
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
            str_contains($track, '*'),
        );
    }

    private function getMilestoneModel(Line $line, Context &$context): Milestone
    {
        list('key' => $key, 'attributes' => $attributes) = $line->parts;
        list('marker' => $markerOffset) = $line->offsets;
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

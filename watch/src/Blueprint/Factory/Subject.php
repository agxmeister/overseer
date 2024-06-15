<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Model\Director;
use Watch\Blueprint\Factory\Model\Subject\Issue as IssueBuilder;
use Watch\Blueprint\Factory\Model\Subject\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Subject as SubjectBlueprint;
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

    public function create(string $content): SubjectBlueprint
    {
        $context = $this->getContext($content, self::PATTERN_CONTEXT_LINE);

        $director = new Director();

        $issueBuilder = new IssueBuilder($this->mapper);
        $director->run(
            $issueBuilder,
            $context,
            self::PATTERN_ISSUE_LINE,
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $issueModels = $issueBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $director->run($milestoneBuilder, $context, self::PATTERN_MILESTONE_LINE, key: 'PRJ');
        $milestoneModels = $milestoneBuilder->flush();

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new SubjectBlueprint($issueModels, $milestoneModels, $nowDate, $isEndMarkers);
    }
}

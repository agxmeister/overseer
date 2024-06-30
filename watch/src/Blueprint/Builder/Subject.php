<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Model\Builder\Director;
use Watch\Blueprint\Model\Builder\Subject\Issue as IssueBuilder;
use Watch\Blueprint\Model\Builder\Subject\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Subject as SubjectBlueprint;
use Watch\Schedule\Mapper;

class Subject extends Builder
{
    private ?SubjectBlueprint $blueprint = null;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_REFERENCE_LINE = '/(?<marker>>)\s*(?<attributes>.*)/';

    public function __construct(private Mapper $mapper)
    {
    }

    public function clean(): self
    {
        $this->blueprint = null;
        return parent::clean();
    }

    public function setContent(): self
    {
        $context = $this->getContext($this->getLines($this->drawing), self::PATTERN_REFERENCE_LINE);

        $director = new Director();

        $issueBuilder = new IssueBuilder($this->mapper);
        $issueParser = new Parser(self::PATTERN_ISSUE_LINE);
        $director->run(
            $issueBuilder,
            $issueParser,
            $context,
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $issueModels = $issueBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneParser = new Parser(self::PATTERN_MILESTONE_LINE);
        $director->run($milestoneBuilder, $milestoneParser, $context, key: 'PRJ');
        $milestoneModels = $milestoneBuilder->flush();

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getReferenceMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        $this->blueprint = new SubjectBlueprint($issueModels, $milestoneModels, $nowDate, $isEndMarkers);

        return $this;
    }

    public function flush(): SubjectBlueprint
    {
        return $this->blueprint;
    }
}

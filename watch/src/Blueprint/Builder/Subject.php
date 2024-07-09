<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Model\Builder\Director;
use Watch\Blueprint\Model\Builder\Subject\Issue as IssueBuilder;
use Watch\Blueprint\Model\Builder\Subject\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Subject\Issue;
use Watch\Blueprint\Subject as SubjectBlueprint;
use Watch\Schedule\Mapper;

class Subject extends Builder
{
    private ?int $trackMarkerOffset = null;
    private ?int $projectMarkerOffset = null;

    /** @var Issue[] */
    private ?array $issueModels = null;

    /** @var Milestone[] */
    private ?array $milestoneModels = null;

    private ?DateTimeImmutable $nowDate;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_REFERENCE_LINE = '/(?<marker>>)\s*(?<attributes>.*)/';

    public function __construct(Drawing $drawing, readonly private Mapper $mapper)
    {
        parent::__construct($drawing);
    }

    public function clean(): self
    {
        $this->trackMarkerOffset = null;
        $this->projectMarkerOffset = null;
        $this->issueModels = null;
        $this->milestoneModels = null;
        return parent::clean();
    }

    public function setModels(): self
    {
        $director = new Director();

        $issueBuilder = new IssueBuilder($this->mapper);
        $issueParser = new Parser(self::PATTERN_ISSUE_LINE);
        $director->run(
            $issueBuilder,
            $issueParser,
            $this->drawing->strokes,
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $this->issueModels = $issueBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneParser = new Parser(self::PATTERN_MILESTONE_LINE);
        $director->run($milestoneBuilder, $milestoneParser, $this->drawing->strokes, key: 'PRJ');
        $this->milestoneModels = $milestoneBuilder->flush();

        $this->trackMarkerOffset = $issueBuilder->getEndPosition();
        $this->projectMarkerOffset = $milestoneBuilder->getMarkerOffset();

        return $this;
    }

    public function setNowDate(): self
    {
        $projectLine = array_reduce(
            $this->milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $this->referenceMarkerOffset - $this->projectMarkerOffset;
        $this->nowDate =  $projectLine?->getDate()->modify("{$gap} day");
        return $this;
    }

    public function flush(): SubjectBlueprint
    {
        return new SubjectBlueprint(
            $this->issueModels,
            $this->milestoneModels,
            $this->nowDate,
            $this->projectMarkerOffset >= $this->trackMarkerOffset,
        );
    }
}

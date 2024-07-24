<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Builder\Asset\Reference;
use Watch\Blueprint\Model\Builder\Director;
use Watch\Blueprint\Model\Builder\Subject\Issue as IssueBuilder;
use Watch\Blueprint\Model\Builder\Subject\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Subject\Issue;
use Watch\Blueprint\Subject as SubjectBlueprint;
use Watch\Schedule\Mapper;

class Subject extends Builder
{
    use HasProjectAsReference;

    private ?int $trackMarkerOffset = null;
    private ?int $projectMarkerOffset = null;

    /** @var Issue[] */
    private ?array $issueModels = null;

    /** @var Milestone[] */
    private ?array $milestoneModels = null;

    const string PATTERN_ISSUE_STROKE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)(?<beginMarker>\|)(?<track>[*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_STROKE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_REFERENCE_STROKE = '/(?<marker>>)\s*(?<attributes>.*)/';

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
        $issueParser = new Parser(self::PATTERN_ISSUE_STROKE);
        $issueStrokes = $this->drawing->getStrokes(
            $issueParser,
            'attributes',
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $director->run($issueBuilder, $issueStrokes);
        $this->issueModels = $issueBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneParser = new Parser(self::PATTERN_MILESTONE_STROKE);
        $milestoneStrokes = $this->drawing->getStrokes(
            $milestoneParser,
            'attributes',
            key: 'PRJ',
        );
        $director->run($milestoneBuilder, $milestoneStrokes);
        $this->milestoneModels = $milestoneBuilder->flush();

        $this->trackMarkerOffset = $issueBuilder->getEndPosition();
        $this->projectMarkerOffset = $milestoneBuilder->getMarkerOffset();

        return $this;
    }

    public function flush(): SubjectBlueprint
    {
        return new SubjectBlueprint(
            $this->issueModels,
            $this->milestoneModels,
            $this?->reference?->date,
            $this->projectMarkerOffset >= $this->trackMarkerOffset,
        );
    }
}

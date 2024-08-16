<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Model\Builder\Director;
use Watch\Blueprint\Model\Builder\Subject\Issue as IssueBuilder;
use Watch\Blueprint\Model\Builder\Subject\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Subject\Issue;
use Watch\Blueprint\Subject as SubjectBlueprint;
use Watch\Config;
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

    public function __construct(Config $config, readonly private Mapper $mapper)
    {
        parent::__construct($config);
    }

    public function clean(): self
    {
        $this->trackMarkerOffset = null;
        $this->projectMarkerOffset = null;
        $this->issueModels = null;
        $this->milestoneModels = null;
        return parent::clean();
    }

    public function setModels(Drawing $drawing): self
    {
        $director = new Director();
        $this->issueModels = $this->setIssueModels($drawing, $director);
        $this->milestoneModels = $this->setMilestoneModels($drawing, $director);
        return $this;
    }

    /**
     * @param Director $director
     * @return Issue[]
     */
    private function setIssueModels(Drawing $drawing, Director $director): array
    {
        $builder = new IssueBuilder($this->mapper);
        $parser = new Parser(
            $this->config->get('blueprint.drawing.stroke.pattern.issue.subject'),
            project: 'PRJ',
            milestone: null,
            type: 'T',
            modifier: null,
        );
        $strokes = $drawing->getStrokes($parser);
        $director->run($builder, $strokes);
        $this->trackMarkerOffset = $builder->getEndPosition();
        return $builder->flush();
    }

    /**
     * @param Director $director
     * @return Milestone[]
     */
    private function setMilestoneModels(Drawing $drawing, Director $director): array
    {
        $builder = new MilestoneBuilder();
        $parser = new Parser(
            $this->config->get('blueprint.drawing.stroke.pattern.milestone.subject'),
            key: 'PRJ',
        );
        $strokes = $drawing->getStrokes($parser);
        $director->run($builder, $strokes);
        $this->projectMarkerOffset = $builder->getMarkerOffset();
        return $builder->flush();
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

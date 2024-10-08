<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Model\Builder\Director;
use Watch\Blueprint\Model\Builder\Schedule\Buffer as BufferBuilder;
use Watch\Blueprint\Model\Builder\Schedule\Issue as IssueBuilder;
use Watch\Blueprint\Model\Builder\Schedule\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Buffer;
use Watch\Blueprint\Model\Schedule\Issue;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Schedule as ScheduleBlueprint;

class Schedule extends Builder
{
    use HasProjectAsReference;

    private ?int $trackMarkerOffset = null;
    private ?int $projectMarkerOffset = null;

    /** @var Issue[] */
    private ?array $issueModels = null;
    /** @var Buffer[] */
    private ?array $bufferModels = null;
    /** @var Milestone[] */
    private ?array $milestoneModels = null;

    public function clean(): self
    {
        $this->trackMarkerOffset = null;
        $this->projectMarkerOffset = null;
        $this->issueModels = null;
        $this->bufferModels = null;
        $this->milestoneModels = null;
        return parent::clean();
    }

    public function setModels(Drawing $drawing): self
    {
        $director = new Director();
        $this->issueModels = $this->setIssueModels($drawing, $director);
        $this->bufferModels = $this->setBufferModels($drawing, $director);
        $this->milestoneModels = $this->setMilestoneModels($drawing, $director);
        return $this;
    }

    /**
     * @param Drawing $drawing
     * @param Director $director
     * @return Issue[]
     */
    private function setIssueModels(Drawing $drawing, Director $director): array
    {
        $builder = new IssueBuilder();
        $parser = new Parser(
            $this->config->get('blueprint.drawing.stroke.pattern.issue.schedule'),
            project: 'PRJ',
            milestone: null,
            type: 'T',
            modifier: null,
        );
        $strokes = $drawing->getStrokes($parser);
        $director->run($builder, $strokes);
        $this->trackMarkerOffset = max($builder->getEndPosition(), $this->trackMarkerOffset);
        return $builder->flush();
    }

    /**
     * @param Drawing $drawing
     * @param Director $director
     * @return Buffer[]
     */
    private function setBufferModels(Drawing $drawing, Director $director): array
    {
        $builder = new BufferBuilder();
        $parser = new Parser(
            $this->config->get('blueprint.drawing.stroke.pattern.buffer.schedule'),
            type: 'T',
        );
        $strokes = $drawing->getStrokes($parser);
        $director->run($builder, $strokes);
        $this->trackMarkerOffset = max($builder->getEndPosition(), $this->trackMarkerOffset);
        return $builder->flush();
    }

    /**
     * @param Drawing $drawing
     * @param Director $director
     * @return Milestone[]
     */
    private function setMilestoneModels(Drawing $drawing, Director $director): array
    {
        $builder = new MilestoneBuilder();
        $parser = new Parser(
            $this->config->get('blueprint.drawing.stroke.pattern.milestone.schedule'),
            key: 'PRJ',
        );
        $strokes = $drawing->getStrokes($parser);
        $director->run($builder, $strokes);
        $this->projectMarkerOffset = $builder->getMarkerOffset();
        return $builder->flush();
    }

    public function flush(): ScheduleBlueprint
    {
        return new ScheduleBlueprint(
            $this->issueModels,
            $this->bufferModels,
            $this->milestoneModels,
            $this?->reference?->date,
            $this->projectMarkerOffset >= $this->trackMarkerOffset,
        );
    }
}

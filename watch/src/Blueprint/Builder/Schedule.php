<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Builder\Stroke\Parser;
use Watch\Blueprint\Builder\Stroke\Stroke;
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
    private ?int $trackMarkerOffset = null;
    private ?int $projectMarkerOffset = null;

    /** @var Issue[] */
    private ?array $issueModels = null;

    /** @var Buffer[] */
    private ?array $bufferModels = null;

    /** @var Milestone[] */
    private ?array $milestoneModels = null;

    const string PATTERN_ISSUE_STROKE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_STROKE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_STROKE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_REFERENCE_STROKE = '/(?<marker>>)\s*(?<attributes>.*)/';

    public function clean(): self
    {
        $this->trackMarkerOffset = null;
        $this->projectMarkerOffset = null;
        $this->issueModels = null;
        $this->bufferModels = null;
        $this->milestoneModels = null;
        return parent::clean();
    }

    public function setModels(): self
    {
        $director = new Director();

        $issueBuilder = new IssueBuilder();
        $issueParser = new Parser(self::PATTERN_ISSUE_STROKE);
        $issueStrokes = $this->drawing->getStrokes(
            $issueParser,
            'attributes',
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $director->run(
            $issueBuilder,
            $issueStrokes,
        );
        $this->issueModels = $issueBuilder->flush();

        $bufferBuilder = new BufferBuilder();
        $bufferParser = new Parser(self::PATTERN_BUFFER_STROKE);
        $bufferStrokes = $this->drawing->getStrokes(
            $bufferParser,
            'attributes',
            type: 'T',
        );
        $director->run($bufferBuilder, $bufferStrokes);
        $this->bufferModels = $bufferBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneParser = new Parser(self::PATTERN_MILESTONE_STROKE);
        $milestoneStrokes = $this->drawing->getStrokes(
            $milestoneParser,
            'attributes',
            key: 'PRJ',
        );
        $director->run($milestoneBuilder, $milestoneStrokes);
        $this->milestoneModels = $milestoneBuilder->flush();

        $this->trackMarkerOffset = max($issueBuilder->getEndPosition(), $bufferBuilder->getEndPosition());
        $this->projectMarkerOffset = $milestoneBuilder->getMarkerOffset();

        return $this;
    }

    protected function getReferenceDate(?Stroke $referenceStroke): ?DateTimeImmutable
    {
        $referenceDate = parent::getReferenceDate($referenceStroke);
        if (!is_null($referenceDate)) {
            return $referenceDate;
        }

        $projectStroke = array_reduce(
            $this->milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $this->getReferenceMarkerOffset($referenceStroke) - $this->projectMarkerOffset;
        return $projectStroke?->getDate()->modify("{$gap} day");
    }

    public function flush(): ScheduleBlueprint
    {
        return new ScheduleBlueprint(
            $this->issueModels,
            $this->bufferModels,
            $this->milestoneModels,
            $this->reference->date,
            $this->projectMarkerOffset >= $this->trackMarkerOffset,
        );
    }
}

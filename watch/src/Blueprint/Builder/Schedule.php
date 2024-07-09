<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
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

    private ?DateTimeImmutable $nowDate = null;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_REFERENCE_LINE = '/(?<marker>>)\s*(?<attributes>.*)/';

    public function clean(): self
    {
        $this->trackMarkerOffset = null;
        $this->projectMarkerOffset = null;
        $this->issueModels = null;
        $this->bufferModels = null;
        $this->milestoneModels = null;
        $this->nowDate = null;
        return parent::clean();
    }

    public function setModels(): self
    {
        $director = new Director();

        $issueBuilder = new IssueBuilder();
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

        $bufferBuilder = new BufferBuilder();
        $bufferParser = new Parser(self::PATTERN_BUFFER_LINE);
        $director->run($bufferBuilder, $bufferParser, $this->drawing->strokes, type: 'T');
        $this->bufferModels = $bufferBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneParser = new Parser(self::PATTERN_MILESTONE_LINE);
        $director->run($milestoneBuilder, $milestoneParser, $this->drawing->strokes, key: 'PRJ');
        $this->milestoneModels = $milestoneBuilder->flush();

        $this->trackMarkerOffset = max($issueBuilder->getEndPosition(), $bufferBuilder->getEndPosition());
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

    public function flush(): ScheduleBlueprint
    {
        return new ScheduleBlueprint(
            $this->issueModels,
            $this->bufferModels,
            $this->milestoneModels,
            $this->nowDate,
            $this->projectMarkerOffset >= $this->trackMarkerOffset,
        );
    }
}

<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Model\Director;
use Watch\Blueprint\Factory\Model\Schedule\Issue as IssueBuilder;
use Watch\Blueprint\Factory\Model\Schedule\Buffer as BufferBuilder;
use Watch\Blueprint\Factory\Model\Schedule\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Schedule as ScheduleBlueprint;

readonly class Schedule
{
    use HasContext;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)\s*(?<attributes>.*)/';

    public function create(string $content): ScheduleBlueprint
    {
        $context = $this->getContext($content, self::PATTERN_CONTEXT_LINE);

        $director = new Director();

        $issueBuilder = new IssueBuilder();
        $director->run(
            $issueBuilder,
            $context,
            self::PATTERN_ISSUE_LINE,
            project: 'PRJ',
            milestone: null,
            type: 'T',
        );
        $issueModels = $issueBuilder->flush();

        $bufferBuilder = new BufferBuilder();
        $director->run($bufferBuilder, $context, self::PATTERN_BUFFER_LINE, type: 'T');
        $bufferModels = $bufferBuilder->flush();

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

        return new ScheduleBlueprint($issueModels, $bufferModels, $milestoneModels, $nowDate, $isEndMarkers);
    }
}

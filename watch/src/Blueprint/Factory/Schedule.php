<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Model\Director;
use Watch\Blueprint\Factory\Model\Schedule\Issue as IssueBuilder;
use Watch\Blueprint\Factory\Model\Schedule\Buffer as BufferBuilder;
use Watch\Blueprint\Factory\Model\Schedule\Milestone as MilestoneBuilder;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Schedule as ScheduleBlueprintModel;

readonly class Schedule
{
    use HasContext;

    const string PATTERN_ISSUE_LINE = '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)(?<beginMarker>\|)(?<track>[x*.\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_MILESTONE_LINE = '/\s*(?<key>[\w\-]+)?\s+(?<marker>\^)\s+(?<attributes>.*)/';
    const string PATTERN_BUFFER_LINE = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<beginMarker>\|)(?<track>[_!\s]*)(?<endMarker>\|)\s*(?<attributes>.*)/';
    const string PATTERN_CONTEXT_LINE = '/(?<marker>>)/';

    public function create(string $content): ScheduleBlueprintModel
    {
        $context = $this->getContext($content, self::PATTERN_CONTEXT_LINE);

        $issueBuilder = new IssueBuilder();
        $issueDirector = new Director(
            self::PATTERN_ISSUE_LINE, ['project' => 'PRJ', 'milestone' => null, 'type' => 'T'],
        );
        $issueDirector->run($issueBuilder, $context);
        $issueModels = $issueBuilder->flush();

        $bufferBuilder = new BufferBuilder();
        $bufferDirector = new Director(self::PATTERN_BUFFER_LINE, ['type' => 'T']);
        $bufferDirector->run($bufferBuilder, $context);
        $bufferModels = $bufferBuilder->flush();

        $milestoneBuilder = new MilestoneBuilder();
        $milestoneDirector = new Director(self::PATTERN_MILESTONE_LINE, ['key' => 'PRJ']);
        $milestoneDirector->run($milestoneBuilder, $context);
        $milestoneModels = $milestoneBuilder->flush();

        $isEndMarkers = $context->getProjectMarkerOffset() >= $context->getIssuesEndPosition();

        $projectLine = array_reduce(
            $milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        $gap = $context->getContextMarkerOffset() - $context->getProjectMarkerOffset();
        $nowDate =  $projectLine?->getDate()->modify("{$gap} day");

        return new ScheduleBlueprintModel($issueModels, $bufferModels, $milestoneModels, $nowDate, $isEndMarkers);
    }
}

<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;

readonly class Mapper
{
    public function __construct(
        public array $startedIssueStates,
        public array $completedIssueStates,
        public array $sequenceLinkTypes,
        public array $scheduleLnkTypes,
    )
    {
    }

    public function getIssueState(string $state): string
    {
        return match (true) {
            in_array($state, $this->startedIssueStates) => Issue::STATE_STARTED,
            in_array($state, $this->completedIssueStates) => Issue::STATE_COMPLETED,
            default => Issue::STATE_UNKNOWN,
        };
    }

    public function getLinkType(string $linkType): string
    {
        return match (true) {
            in_array($linkType, $this->sequenceLinkTypes) => Link::TYPE_SEQUENCE,
            in_array($linkType, $this->scheduleLnkTypes) => Link::TYPE_SCHEDULE,
            default => Link::TYPE_UNKNOWN,
        };
    }
}

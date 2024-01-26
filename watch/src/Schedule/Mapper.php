<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Issue;
use Watch\Schedule\Model\Link;

readonly class Mapper
{
    public function __construct(
        private array $startedTaskStates,
        private array $completedTaskStates,
        private array $sequenceLinkTypes,
        private array $scheduleLnkTypes,
    )
    {
    }

    public function getIssueState(string $state): string
    {
        return match (true) {
            in_array($state, $this->startedTaskStates) => Issue::STATE_STARTED,
            in_array($state, $this->completedTaskStates) => Issue::STATE_COMPLETED,
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

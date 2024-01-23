<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Task;

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

    public function getTaskState(string $state): string
    {
        return match (true) {
            in_array($state, $this->startedTaskStates) => Task::STATE_STARTED,
            in_array($state, $this->completedTaskStates) => Task::STATE_COMPLETED,
            default => Task::STATE_UNKNOWN,
        };
    }

    public function getLinkType(string $jointType): string
    {
        return match (true) {
            in_array($jointType, $this->sequenceLinkTypes) => Link::TYPE_SEQUENCE,
            in_array($jointType, $this->scheduleLnkTypes) => Link::TYPE_SCHEDULE,
            default => Link::TYPE_UNKNOWN,
        };
    }
}

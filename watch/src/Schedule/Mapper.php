<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Task;

readonly class Mapper
{
    public function __construct(
        private array $sequenceJoints,
        private array $scheduleJoints,
        private array $startedStatuses,
        private array $completedStatuses,
    )
    {
    }

    public function getLinkType(string $jointType): string
    {
        return match (true) {
            in_array($jointType, $this->sequenceJoints) => Link::TYPE_SEQUENCE,
            in_array($jointType, $this->scheduleJoints) => Link::TYPE_SCHEDULE,
            default => Link::TYPE_UNKNOWN,
        };
    }

    public function getState(string $status): string
    {
        return match (true) {
            in_array($status, $this->startedStatuses) => Task::STATE_STARTED,
            in_array($status, $this->completedStatuses) => Task::STATE_COMPLETED,
            default => Task::STATE_UNKNOWN,
        };
    }
}

<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Link;

readonly class Mapper
{
    public function __construct(private array $sequenceJoints, private array $scheduleJoints)
    {
    }

    public function getLinkType($jointType)
    {
        return match (true) {
            in_array($jointType, $this->sequenceJoints) => Link::TYPE_SEQUENCE,
            in_array($jointType, $this->scheduleJoints) => Link::TYPE_SCHEDULE,
            default => Link::TYPE_UNKNOWN,
        };
    }
}

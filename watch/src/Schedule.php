<?php

namespace Watch;

use Watch\Schedule\Model\Milestone;

readonly class Schedule
{
    /**
     * @param Milestone[] $milestones
     */
    public function __construct(public array $milestones)
    {
    }
}

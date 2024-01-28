<?php

namespace Watch\Schedule\Model;

readonly class Schedule
{
    /**
     * @param Milestone[] $milestones
     */
    public function __construct(public array $milestones)
    {
    }
}

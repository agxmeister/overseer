<?php

namespace Watch;

use Watch\Schedule\Model\Project;

readonly class Schedule
{
    public function __construct(public Project $project){
    }
}

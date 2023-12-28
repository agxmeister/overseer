<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Model\Task;
use Watch\Subject\Model\Issue;

interface ConvertStrategy
{
    public function getTask(Issue $issue): Task;
}

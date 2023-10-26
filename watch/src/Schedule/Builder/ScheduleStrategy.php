<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Model\Node;

interface ScheduleStrategy
{
    public function apply(Node $milestone): void;
}

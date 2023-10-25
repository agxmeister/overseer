<?php

namespace Watch\Schedule\Strategy\Schedule;

use Watch\Schedule\Model\Node;

interface Strategy
{
    public function apply(Node $milestone): void;
}

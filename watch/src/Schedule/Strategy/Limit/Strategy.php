<?php

namespace Watch\Schedule\Strategy\Limit;

use Watch\Schedule\Model\Node;

interface Strategy
{
    public function apply(Node $milestone): void;
}

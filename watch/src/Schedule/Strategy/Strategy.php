<?php

namespace Watch\Schedule\Strategy;

use Watch\Schedule\Model\Node;

interface Strategy
{
    public function schedule(Node $milestone): void;
}

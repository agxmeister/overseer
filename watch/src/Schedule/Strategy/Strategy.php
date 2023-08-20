<?php

namespace Watch\Schedule\Strategy;

use Watch\Schedule\Node;

interface Strategy
{
    public function schedule(Node $milestone): void;
}

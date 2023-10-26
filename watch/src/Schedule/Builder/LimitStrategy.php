<?php

namespace Watch\Schedule\Builder;

use Watch\Schedule\Model\Node;

interface LimitStrategy
{
    public function apply(Node $milestone): void;
}

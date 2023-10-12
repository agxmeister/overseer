<?php

namespace Watch\Schedule\Strategy;

use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;

class Test implements Strategy
{
    public function schedule(Node $milestone): void
    {
        $preceders = $milestone->getPreceders();
        $preceders[1]->unprecede($milestone);
        $preceders[1]->precede($preceders[0], Link::TYPE_SCHEDULE);
    }
}

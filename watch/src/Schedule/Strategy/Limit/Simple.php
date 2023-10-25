<?php

namespace Watch\Schedule\Strategy\Limit;

use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;

class Simple implements Strategy
{
    public function apply(Node $milestone): void
    {
        $preceders = $milestone->getPreceders();
        for ($i = 0; $i < sizeof($preceders) - 1; $i++) {
            $preceders[$i + 1]->unprecede($milestone);
            $preceders[$i + 1]->precede($preceders[$i], Link::TYPE_SCHEDULE);
        }
    }
}

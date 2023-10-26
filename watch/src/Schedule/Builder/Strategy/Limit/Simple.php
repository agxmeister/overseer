<?php

namespace Watch\Schedule\Builder\Strategy\Limit;

use Watch\Schedule\Builder\LimitStrategy;
use Watch\Schedule\Model\Link;
use Watch\Schedule\Model\Node;

class Simple implements LimitStrategy
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

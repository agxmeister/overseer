<?php

namespace Watch\Schedule\Builder;

use Watch\Subject\Model\Issue;
use Watch\Schedule\Builder;
use Watch\Schedule\Model\Buffer;
use Watch\Schedule\Model\Node;

class Preserving implements Builder
{
    use AbleToBuild;

    /**
     * @param Context $context
     * @param Issue[] $issues
     * @param LimitStrategy|null $limitStrategy
     */
    public function __construct(
        protected readonly Context $context,
        protected readonly array $issues,
        private readonly LimitStrategy|null $limitStrategy = null
    )
    {
    }

    public function addDates(): self
    {
        $this->addBuffersDates(array_filter(
            $this->milestone->getPreceders(true),
            fn(Node $node) => $node instanceof Buffer,
        ));
        $this->addMilestoneDates($this->milestone);
        return $this;
    }
}

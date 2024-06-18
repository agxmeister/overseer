<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Factory\Context;

trait HasContext
{
    private ?Context $context;

    public function setContext(Context $context): Builder
    {
        $this->context = $context;
        return $this;
    }
}

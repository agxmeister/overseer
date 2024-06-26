<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Context;

trait HasContext
{
    private ?Context $context;

    public function setContext(Context $context): Builder
    {
        $this->context = $context;
        return $this;
    }
}

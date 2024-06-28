<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Context;

abstract class Builder
{
    protected ?Context $context;

    public function setContext(Context $context): self
    {
        $this->context = $context;
        return $this;
    }

    abstract public function reset(): self;
    abstract public function release(): self;
    abstract public function setModel(array $values, array $offsets, ...$defaults): self;
}

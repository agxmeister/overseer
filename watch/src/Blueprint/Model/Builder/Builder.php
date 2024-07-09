<?php

namespace Watch\Blueprint\Model\Builder;

abstract class Builder
{
    abstract public function reset(): self;
    abstract public function release(): self;
    abstract public function setModel(array $values, array $offsets, ...$defaults): self;
}

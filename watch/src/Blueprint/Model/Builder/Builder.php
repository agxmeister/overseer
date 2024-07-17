<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Stroke\Stroke;

abstract class Builder
{
    abstract public function reset(): self;
    abstract public function release(): self;
    abstract public function setModel(Stroke $stroke): self;
}

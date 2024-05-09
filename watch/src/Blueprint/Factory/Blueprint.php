<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Blueprint as BlueprintModel;

abstract readonly class Blueprint
{
    abstract public function create(string $content): BlueprintModel;
}

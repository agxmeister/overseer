<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Builder\Asset\Drawing;

readonly class Director
{
    public function build(Builder $builder, Drawing $drawing): void
    {
        $builder
            ->clean()
            ->setModels($drawing)
            ->setReference($drawing);
    }
}

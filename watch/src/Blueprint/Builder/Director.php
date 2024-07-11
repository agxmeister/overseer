<?php

namespace Watch\Blueprint\Builder;

readonly class Director
{
    public function build(Builder $builder): void
    {
        $builder
            ->clean()
            ->setModels()
            ->setReference();
    }
}

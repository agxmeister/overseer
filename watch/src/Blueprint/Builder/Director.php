<?php

namespace Watch\Blueprint\Builder;

readonly class Director
{
    public function build(Builder $builder, string $drawing): void
    {
        $builder
            ->clean()
            ->setDrawing($drawing)
            ->setContext()
            ->setContent();
    }
}

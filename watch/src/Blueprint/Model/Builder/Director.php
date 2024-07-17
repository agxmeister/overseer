<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Stroke\Parser;

readonly class Director
{
    public function run(Builder $builder, array $strokes): void
    {
        foreach ($strokes as $stroke) {
            $builder
                ->setModel($stroke)
                ->release();
        }
    }
}

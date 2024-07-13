<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Stroke\Parser;

readonly class Director
{
    public function run(Builder $builder, Parser $parser, array $strokes, ...$defaults): void
    {
        foreach ($parser->getMatches($strokes) as $match) {
            list($values, $offsets) = $match;
            $builder
                ->setModel($values, $offsets, ...$defaults)
                ->release();
        }
    }
}

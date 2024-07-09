<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Parser;

readonly class Director
{
    public function run(Builder $builder, Parser $parser, array $lines, ...$defaults): void
    {
        foreach ($parser->getMatches($lines) as $match) {
            list($values, $offsets) = $match;
            $builder
                ->setModel($values, $offsets, ...$defaults)
                ->release();
        }
    }
}

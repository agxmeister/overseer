<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Context;
use Watch\Blueprint\Builder\Parser;

readonly class Director
{
    public function run(Builder $builder, Parser $parser, array $lines, Context $context, ...$defaults): void
    {
        foreach ($parser->getMatches($lines) as $match) {
            list($values, $offsets) = $match;
            $builder
                ->setContext($context)
                ->setModel($values, $offsets, ...$defaults)
                ->release();
        }
    }
}

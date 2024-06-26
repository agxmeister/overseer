<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Context;
use Watch\Blueprint\Builder\Parser;

readonly class Director
{
    public function run(Builder $builder, Parser $parser, Context $context, ...$defaults): void
    {
        foreach ($parser->getMatches($context->lines) as $match) {
            list($values, $offsets) = $match;
            $builder
                ->setContext($context)
                ->setModel($values, $offsets, ...$defaults)
                ->release();
        }
    }
}

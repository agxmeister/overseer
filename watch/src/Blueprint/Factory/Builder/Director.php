<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Factory\Context;
use Watch\Blueprint\Factory\Parser;

readonly class Director
{
    public function run(Builder $builder, Context $context, string $pattern, ...$defaults): void
    {
        $parser = new Parser($pattern);
        foreach ($parser->getMatches($context->lines) as $match) {
            list($values, $offsets) = $match;
            $builder
                ->setContext($context)
                ->setModel($values, $offsets, ...$defaults)
                ->release();
        }
    }
}

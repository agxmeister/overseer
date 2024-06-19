<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Factory\Context;

readonly class Director
{
    public function run(Builder $builder, Context $context, string $pattern, ...$defaults): void
    {
        foreach (
            array_filter(
                $context->lines,
                fn($line) => preg_match($pattern, $line),
            ) as $content
        ) {
            $builder
                ->setContext($context)
                ->setModel($content, $pattern, ...$defaults)
                ->release();
        }
    }
}

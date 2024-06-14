<?php

namespace Watch\Blueprint\Factory\Model;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Factory\Line;

readonly class Director
{
    public function run(Builder $builder, Context $context, string $pattern, ...$defaults): void
    {
        foreach (
            array_filter(
                $context->lines,
                fn($line) => preg_match($pattern, $line),
            ) as $line
        ) {
            $builder
                ->setModel(
                    new Line($line, $pattern, ...$defaults),
                    $context,
                )
                ->release();
        }
    }
}

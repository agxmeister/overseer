<?php

namespace Watch\Blueprint\Factory\Model;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Factory\Line;

readonly class Director
{
    public function __construct(private string $pattern, private array $defaults)
    {
    }

    public function run(Builder $builder, Context $context): void
    {
        array_reduce(
            array_filter(
                $context->lines,
                fn($line) => preg_match($this->pattern, $line),
            ),
            fn($acc, $line) => $builder
                ->reset()
                ->setModel(
                    new Line($line, $this->pattern, ...$this->defaults),
                    $context,
                )
                ->release(),
            $builder,
        );
    }
}

<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Stroke
{
    public array $parts;

    public function __construct(array $parts, public array $offsets, public array $attributes, ...$defaults)
    {
        $this->parts = array_merge(
            $defaults,
            array_filter(
                $parts,
                fn($value) => !is_null($value),
            ),
        );
    }
}

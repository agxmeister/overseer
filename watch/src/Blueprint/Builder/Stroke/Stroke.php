<?php

namespace Watch\Blueprint\Builder\Stroke;

readonly class Stroke
{
    use HasAttributes;

    public array $parts;
    public array $offsets;

    public function __construct(array $values, array $offsets, ...$defaults)
    {
        $this->parts = array_merge(
            $defaults,
            array_filter(
                $values,
                fn($value) => !is_null($value),
            ),
        );
        $this->offsets = $offsets;
    }
}

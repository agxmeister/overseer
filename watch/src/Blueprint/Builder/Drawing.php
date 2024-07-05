<?php

namespace Watch\Blueprint\Builder;

readonly class Drawing
{
    public array $strokes;

    public function __construct(string $drawing)
    {
        $this->strokes = array_filter(
            explode("\n", $drawing),
            fn($line) => !empty(trim($line)),
        );
    }
}

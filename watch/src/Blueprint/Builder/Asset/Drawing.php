<?php

namespace Watch\Blueprint\Builder\Asset;

use Watch\Blueprint\Builder\Stroke\Parser;
use Watch\Blueprint\Builder\Stroke\Stroke;

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

    public function getStroke(Parser $parser): ?Stroke
    {
        return array_reduce(
            $parser->getMatches($this->strokes),
            fn($acc, $match) => new Stroke($match[0], $match[1]),
        );
    }
}

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

    public function getStroke(Parser $parser, string $attributesPartName): ?Stroke
    {
        $match = current($parser->getMatches($this->strokes));
        if ($match === false) {
            return null;
        }
        [$parts, $offsets] = $match;
        $attributes = $parts[$attributesPartName] ?? '';
        $filteredParts = array_filter(
            $parts,
            fn(string $key) => $key !== $attributesPartName,
            ARRAY_FILTER_USE_KEY
        );
        return new Stroke($filteredParts, $attributes, $offsets);
    }
}

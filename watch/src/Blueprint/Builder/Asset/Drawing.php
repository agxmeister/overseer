<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Drawing
{
    private array $strokes;

    public function __construct(string $drawing)
    {
        $this->strokes = array_filter(
            explode("\n", $drawing),
            fn($line) => !empty(trim($line)),
        );
    }

    /**
     * @param Parser $parser
     * @return Stroke[]
     */
    public function getStrokes(Parser $parser): array
    {
        return array_map(
            fn(array $match) => $this->createStroke($match),
            $parser->getMatches($this->strokes),
        );
    }

    public function getStroke(Parser $parser): ?Stroke
    {
        $matches = $parser->getMatches($this->strokes);
        if (empty($matches)) {
            return null;
        }
        return $this->createStroke(array_pop($matches));
    }

    private function createStroke($match): Stroke
    {
        [$parts, $offsets] = $match;
        return new Stroke($parts, $offsets);
    }
}

<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Drawing
{
    /**
     * @var string[]
     */
    private array $lines;

    public function __construct(string $content)
    {
        $this->lines = array_filter(
            explode("\n", $content),
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
            $parser->getMatches($this->lines),
        );
    }

    public function getStroke(Parser $parser): ?Stroke
    {
        $matches = $parser->getMatches($this->lines);
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

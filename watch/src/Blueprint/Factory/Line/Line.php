<?php

namespace Watch\Blueprint\Factory\Line;

readonly class Line
{
    public array $parts;
    public array $offsets;

    public function __construct(string $content, string $pattern, ...$defaults)
    {
        $offsets = [];
        $this->parts = array_merge($defaults, $this->getParts($content, $pattern, $offsets));
        $this->offsets = $offsets;
    }

    private function getParts(string $string, string $pattern, &$offsets = []): array
    {
        $matches = [];
        $result = preg_match($pattern, $string, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
        if (!$result) {
            return [];
        }

        $values = array_filter(
            array_map(
                fn($match) => $match[0],
                array_filter(
                    $matches,
                    fn($key) => is_string($key),
                    ARRAY_FILTER_USE_KEY,
                ),
            ),
            fn($value) => !is_null($value),
        );

        $offsets = array_map(
            fn($match) => $match[1],
            array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY,
            ),
        );

        return $values;
    }
}

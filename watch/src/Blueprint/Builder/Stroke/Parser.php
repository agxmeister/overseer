<?php

namespace Watch\Blueprint\Builder\Stroke;

readonly class Parser
{
    public function __construct(private string $pattern)
    {
    }

    /**
     * @param string[] $strokes
     * @return string[]
     */
    public function getMatches(array $strokes): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn(string $line) => $this->getMatch($line),
                    $strokes,
                ),
                fn($match) => !is_null($match),
            )
        );
    }

    private function getMatch(string $stroke): ?array
    {
        $result = preg_match($this->pattern, $stroke, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
        if (!$result) {
            return null;
        }
        $namedMatches = array_filter(
            $matches,
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY,
        );
        return [
            array_map(
                fn($match) => $match[0],
                $namedMatches,
            ),
            array_map(
                fn($match) => $match[1],
                $namedMatches,
            ),
        ];
    }
}

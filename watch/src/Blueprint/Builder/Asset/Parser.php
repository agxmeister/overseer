<?php

namespace Watch\Blueprint\Builder\Asset;

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
        $typedMatches = array_reduce(
            array_keys($namedMatches),
            fn($acc, $key) => [...$acc, ...$this->getTypedMatch($key, $namedMatches[$key])],
            [],
        );
        return [
            array_map(
                fn($match) => $match[0],
                $typedMatches,
            ),
            array_map(
                fn($match) => $match[1],
                $typedMatches,
            ),
        ];
    }

    private function getTypedMatch(string $key, array $match): array
    {
        $parts = explode('_', $key);
        $type = sizeof($parts) < 2 ? 'string' : $parts[array_key_first($parts)];
        return [$parts[array_key_last($parts)] => $type === 'csv' ? $this->getCsv($match) : $match];
    }

    private function getCsv(array $match): array
    {
        $match[0] = array_map(
            fn(string $value) => trim($value),
            explode(',', $match[0]),
        );
        return $match;
    }
}

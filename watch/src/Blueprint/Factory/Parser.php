<?php

namespace Watch\Blueprint\Factory;

class Parser
{
    public function getMatches($pattern, $lines): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn(string $line) => $this->getMatch($pattern, $line),
                    $lines,
                ),
                fn($match) => !is_null($match),
            )
        );
    }

    private function getMatch(string $pattern, string $line): ?array
    {
        $result = preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
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

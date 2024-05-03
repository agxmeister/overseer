<?php

namespace Watch\Description;

class Utils
{
    static public function getStringParts(string $string, string $pattern, &$offsets = [], ...$defaults): array|null
    {
        $matches = [];
        $result = preg_match($pattern, $string, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);
        if (!$result) {
            return null;
        }

        $values = array_map(
            fn($match) => $match[0],
            array_filter(
                $matches,
                fn($key) => is_string($key),
                ARRAY_FILTER_USE_KEY,
            ),
        );
        array_walk(
            $values,
            fn(&$value, $key) => $value = $value ?? $defaults[$key] ?? null,
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

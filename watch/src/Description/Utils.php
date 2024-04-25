<?php

namespace Watch\Description;

class Utils
{
    static public function getStringParts(string $string, string $pattern, ...$defaults): array
    {
        $matches = [];
        preg_match($pattern, $string, $matches, PREG_UNMATCHED_AS_NULL);
        array_walk(
            $matches,
            fn(&$value, $key) => $value = $value ?? $defaults[$key] ?? null,
        );
        return array_filter(
            $matches,
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY,
        );
    }
}

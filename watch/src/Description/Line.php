<?php

namespace Watch\Description;

abstract readonly class Line
{
    public function __construct(public string $content)
    {
    }

    protected function getValues($string, $separator, $reverse = false, ...$defaults): array
    {
        return array_map(
            fn($default, $value) => $value ?? $default,
            $defaults,
            array_filter(
                $reverse
                    ? array_reverse(explode($separator, $string))
                    : explode($separator, $string),
                fn(string $part) => !empty($part),
            )
        );
    }

    protected function getValuesByPattern($string, $pattern, ...$defaults): array
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

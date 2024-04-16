<?php

namespace Watch\Description;

abstract readonly class Line
{
    public function __construct(public string $content)
    {
    }

    protected function getValues($string, $separator, $defaults, $reverse = false): array
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
}

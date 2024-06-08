<?php

namespace Watch\Blueprint\Factory;

class Line
{
    public readonly array $parts;
    public readonly array $offsets;

    public function __construct(string $content, string $pattern, ...$defaults)
    {
        $offsets = [];
        $this->parts = Utils::getStringParts($content, $pattern, $offsets, ...$defaults);
        $this->offsets = $offsets;
    }
}

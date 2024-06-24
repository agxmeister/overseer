<?php

namespace Watch\Blueprint\Factory;

trait HasLines
{
    protected function getLines(string $content): array
    {
        return array_filter(
            explode("\n", $content),
            fn($line) => !empty(trim($line)),
        );
    }
}

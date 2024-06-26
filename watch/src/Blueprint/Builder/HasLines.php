<?php

namespace Watch\Blueprint\Builder;

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

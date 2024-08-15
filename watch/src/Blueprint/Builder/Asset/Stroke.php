<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Stroke
{
    public function __construct(public array $parts, public array $offsets)
    {
    }
}

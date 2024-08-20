<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Dash
{
    public function __construct(public mixed $value, public ?int $offset)
    {
    }
}

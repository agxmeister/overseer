<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Stroke
{
    /**
     * @param Dash[] $dashes
     */
    public function __construct(public array $dashes)
    {
    }
}

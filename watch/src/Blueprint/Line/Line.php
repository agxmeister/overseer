<?php

namespace Watch\Blueprint\Line;

abstract readonly class Line
{
    /**
     * @param Attribute[] $attributes
     */
    public function __construct(public array $attributes)
    {
    }
}

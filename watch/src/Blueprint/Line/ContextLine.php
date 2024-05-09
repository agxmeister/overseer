<?php

namespace Watch\Blueprint\Line;

readonly class ContextLine extends Line
{
    public function __construct(public int $markerOffset)
    {
        parent::__construct();
    }
}

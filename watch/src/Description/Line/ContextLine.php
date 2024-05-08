<?php

namespace Watch\Description\Line;

use Watch\Description\Line;

readonly class ContextLine extends Line
{
    public function __construct(public int $markerOffset)
    {
        parent::__construct();
    }
}

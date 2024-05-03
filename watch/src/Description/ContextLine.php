<?php

namespace Watch\Description;

readonly class ContextLine extends Line
{
    public function __construct(string $content, public int $markerOffset)
    {
        parent::__construct($content);
    }
}

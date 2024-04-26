<?php

namespace Watch\Description;

readonly class ContextLine extends Line
{
    const string PATTERN = '/>/';

    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '>');
    }
}

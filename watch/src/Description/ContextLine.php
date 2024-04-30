<?php

namespace Watch\Description;

readonly class ContextLine extends Line
{
    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '>');
    }
}

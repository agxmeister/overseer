<?php

namespace Watch\Description;

readonly class ProjectLine extends Line
{
    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '^');
    }
}

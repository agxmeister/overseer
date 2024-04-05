<?php

namespace Watch\Description;

readonly class ContextLine extends Line
{
    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '>');
    }

    protected function getAttributesContent(): string
    {
        return trim(array_reverse(explode('>', $this->content))[0]);
    }
}

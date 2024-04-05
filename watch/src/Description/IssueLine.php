<?php

namespace Watch\Description;

readonly class IssueLine extends Line
{
    public Track $track;

    public function __construct($content)
    {
        parent::__construct($content);
        $this->track = new Track(explode('|', $this->content)[1]);
    }

    protected function getAttributesContent(): string
    {
        return trim(array_reverse(explode('|', $this->content))[0]);
    }
}

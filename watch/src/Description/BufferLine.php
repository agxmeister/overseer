<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct($content, string $key, string $type, string $track, string $attributes)
    {
        parent::__construct($content, $key, $type, $track);
        $this->setAttributes($attributes);
        $this->consumption = substr_count(trim($track), '!');
    }
}

<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct($content, public string $key, public  string $type, string $track, string $attributes)
    {
        parent::__construct($content);
        $this->setTrack($track);
        $this->setAttributes($attributes);
        $this->consumption = substr_count(trim($track), '!');
    }
}

<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct(
        $content,
        string $key,
        string $type,
        string $track,
        string $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($content, $key, $type, $track, $attributes, $endMarkerOffset);
        $this->consumption = substr_count(trim($track), '!');
    }
}

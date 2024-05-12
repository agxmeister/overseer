<?php

namespace Watch\Blueprint\Line;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct(
        string $key,
        string $type,
        string $track,
        array $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($key, $type, $track, $attributes, $endMarkerOffset);
        $this->consumption = substr_count(trim($track), '!');
    }
}

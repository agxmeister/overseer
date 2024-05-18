<?php

namespace Watch\Blueprint\Line;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct(
        string $key,
        string $type,
        Track $track,
        array $attributes,
    )
    {
        parent::__construct($key, $type, $track, $attributes);
        $this->consumption = substr_count(trim($track->content), '!');
    }
}

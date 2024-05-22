<?php

namespace Watch\Blueprint\Model\Schedule;

use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Model\TrackLine;

readonly class BufferLine extends TrackLine
{
    public int $consumption;

    public function __construct(
        public string $key,
        public string $type,
        public Track $track,
        public array $attributes,
    )
    {
        $this->consumption = substr_count(trim($track->content), '!');
    }
}

<?php

namespace Watch\Blueprint\Model\Schedule;

use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Model\WithTrack;

readonly class Buffer implements WithTrack
{
    public function __construct(
        public string $key,
        public string $type,
        public Track $track,
        public array $links,
        public array $attributes,
        public int $consumption,
    )
    {
    }
}

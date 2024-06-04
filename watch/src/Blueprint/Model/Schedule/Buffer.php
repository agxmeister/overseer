<?php

namespace Watch\Blueprint\Model\Schedule;

use Watch\Blueprint\Model\Track;

readonly class Buffer
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

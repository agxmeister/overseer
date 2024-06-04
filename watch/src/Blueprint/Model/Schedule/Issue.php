<?php

namespace Watch\Blueprint\Model\Schedule;

use Watch\Blueprint\Model\Track;

readonly class Issue
{
    public function __construct(
        public string $key,
        public string $type,
        public string $project,
        public string|null $milestone,
        public Track $track,
        public array $links,
        public array $attributes,
        public bool $started,
        public bool $completed,
        public bool $scheduled,
        public bool $critical,
        public bool $ignored,
    )
    {
    }
}

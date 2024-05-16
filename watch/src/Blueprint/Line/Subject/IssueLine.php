<?php

namespace Watch\Blueprint\Line\Subject;

use Watch\Blueprint\Line\IssueLine as AbstractIssueLine;
use Watch\Blueprint\Line\Track;

readonly class IssueLine extends AbstractIssueLine
{
    public function __construct(
        string $key,
        string $type,
        string $project,
        string|null $milestone,
        Track $track,
        array $attributes,
        int $endMarkerOffset,
        public bool $started,
        public bool $completed,
        public bool $scheduled,
    )
    {
        parent::__construct($key, $type, $project, $milestone, $track, $attributes, $endMarkerOffset);
    }
}

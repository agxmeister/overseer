<?php

namespace Watch\Blueprint\Line;

readonly abstract class IssueLine extends TrackLine
{
    public function __construct(
        string $key,
        string $type,
        public string $project,
        public string|null $milestone,
        string $track,
        array $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($key, $type, $track, $attributes, $endMarkerOffset);
    }
}

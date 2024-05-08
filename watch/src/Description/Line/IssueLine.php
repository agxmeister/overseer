<?php

namespace Watch\Description\Line;

readonly abstract class IssueLine extends TrackLine
{
    public function __construct(
        string $key,
        string $type,
        public string $project,
        public string|null $milestone,
        string $track,
        string $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($key, $type, $track, $attributes, $endMarkerOffset);
    }
}

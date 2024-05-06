<?php

namespace Watch\Description;

readonly abstract class IssueLine extends TrackLine
{
    public function __construct(
        $content,
        string $key,
        string $type,
        public string $project,
        public string|null $milestone,
        string $track,
        string $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($content, $key, $type, $track, $attributes, $endMarkerOffset);
    }
}

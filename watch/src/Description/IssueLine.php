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
    )
    {
        parent::__construct($content, $key, $type, $track);
    }
}

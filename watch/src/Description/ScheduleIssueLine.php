<?php

namespace Watch\Description;

readonly class ScheduleIssueLine extends TrackLine
{
    public bool $started;
    public bool $completed;
    public bool $scheduled;
    public bool $critical;
    public bool $ignored;

    public function __construct(
        $content,
        string $key,
        string $type,
        public string $project,
        public string|null $milestone,
        string $modifier,
        string $track,
        string $attributes,
    )
    {
        parent::__construct($content, $key, $type, $track);
        $this->setAttributes($attributes);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->scheduled = str_contains($track, '*') || str_contains($track, 'x');
        $this->critical = str_contains($track, 'x');
        $this->ignored = $modifier === '-';
    }
}

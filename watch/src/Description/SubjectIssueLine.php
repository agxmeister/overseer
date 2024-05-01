<?php

namespace Watch\Description;

readonly class SubjectIssueLine extends TrackLine
{
    public bool $scheduled;
    public bool $started;
    public bool $completed;

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
        $this->scheduled = str_contains($track, '*');
    }
}

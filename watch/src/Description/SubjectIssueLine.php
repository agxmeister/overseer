<?php

namespace Watch\Description;

readonly class SubjectIssueLine extends IssueLine
{
    public bool $scheduled;
    public bool $started;
    public bool $completed;

    public function __construct(
        $content,
        string $key,
        string $type,
        string $project,
        string|null $milestone,
        string $modifier,
        string $track,
        string $attributes,
    )
    {
        parent::__construct($content, $key, $type, $project, $milestone, $track, $attributes);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->scheduled = str_contains($track, '*');
    }
}

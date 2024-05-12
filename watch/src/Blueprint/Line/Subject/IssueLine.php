<?php

namespace Watch\Blueprint\Line\Subject;

use Watch\Blueprint\Line\IssueLine as AbstractIssueLine;

readonly class IssueLine extends AbstractIssueLine
{
    public bool $scheduled;
    public bool $started;
    public bool $completed;

    public function __construct(
        string $key,
        string $type,
        string $project,
        string|null $milestone,
        string $modifier,
        string $track,
        array $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($key, $type, $project, $milestone, $track, $attributes, $endMarkerOffset);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->scheduled = str_contains($track, '*');
    }
}

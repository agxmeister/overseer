<?php

namespace Watch\Blueprint\Line\Schedule;

use Watch\Blueprint\Line\IssueLine as AbstractIssueLine;

readonly class IssueLine extends AbstractIssueLine
{
    public bool $started;
    public bool $completed;
    public bool $scheduled;
    public bool $critical;
    public bool $ignored;

    public function __construct(
        string $key,
        string $type,
        string $project,
        string|null $milestone,
        string $modifier,
        string $track,
        string $attributes,
        int $endMarkerOffset,
    )
    {
        parent::__construct($key, $type, $project, $milestone, $track, $attributes, $endMarkerOffset);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->scheduled = str_contains($track, '*') || str_contains($track, 'x');
        $this->critical = str_contains($track, 'x');
        $this->ignored = $modifier === '-';
    }
}

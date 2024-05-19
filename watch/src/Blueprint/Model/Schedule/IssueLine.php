<?php

namespace Watch\Blueprint\Model\Schedule;

use Watch\Blueprint\Model\IssueLine as AbstractIssueLine;
use Watch\Blueprint\Model\Track;

readonly class IssueLine extends AbstractIssueLine
{
    public function __construct(
        string $key,
        string $type,
        string $project,
        string|null $milestone,
        Track $track,
        array $attributes,
        public bool $started,
        public bool $completed,
        public bool $scheduled,
        public bool $critical,
        public bool $ignored,
    )
    {
        parent::__construct($key, $type, $project, $milestone, $track, $attributes);
    }
}

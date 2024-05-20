<?php

namespace Watch\Blueprint\Model\Subject;

use Watch\Blueprint\Model\IssueLine as AbstractIssueLine;
use Watch\Blueprint\Model\Track;

readonly class IssueLine extends AbstractIssueLine
{
    public function __construct(
        public string $key,
        public string $type,
        public string $project,
        public string|null $milestone,
        public Track $track,
        public array $attributes,
        public bool $started,
        public bool $completed,
        public bool $scheduled,
    )
    {
    }
}

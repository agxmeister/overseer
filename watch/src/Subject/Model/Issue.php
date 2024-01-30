<?php

namespace Watch\Subject\Model;

readonly class Issue
{
    public function __construct(
        public string|null $id = null,
        public string|null $key = null,
        public string|null $summary = null,
        public string|null $status = null,
        public string|null $milestone = null,
        public string|null $project = null,
        public string|null $type = null,
        public int|null    $duration = null,
        public string|null $begin = null,
        public string|null $end = null,
    )
    {
    }
}

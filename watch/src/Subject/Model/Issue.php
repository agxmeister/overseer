<?php

namespace Watch\Subject\Model;

readonly class Issue
{
    public function __construct(
        public string|null $key = null,
        public string|null $summary = null,
        public string|null $status = null,
        public int|null    $duration = null,
        public string|null $begin = null,
        public string|null $end = null,
        public bool|null   $started = null,
        public bool|null   $completed = null,
        public array|null  $links = null,
        public string|null $milestone = null,
    )
    {
    }

    /**
     * @return Link[]
     */
    public function getInwardLinks(): array
    {
        return array_filter($this->links, fn(Link $link) => $link->role === Link::ROLE_INWARD);
    }

    public function getOutwardLinks(): array
    {
        return array_filter($this->links, fn(Link $link) => $link->role === Link::ROLE_OUTWARD);
    }
}

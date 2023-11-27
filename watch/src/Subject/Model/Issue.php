<?php

namespace Watch\Subject\Model;

readonly class Issue
{
    public string|null $key;
    public string|null $summary;
    public string|null $status;
    public string|null $duration;
    public string|null $begin;
    public string|null $end;
    public bool|null $isStarted;
    public bool|null $isCompleted;
    public array|null $links;

    public function __construct(array $properties)
    {
        foreach ($properties as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
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

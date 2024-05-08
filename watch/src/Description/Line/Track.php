<?php

namespace Watch\Description\Line;

readonly class Track
{
    public int $duration;
    public int $gap;

    public function __construct(public string $content)
    {
        $this->duration = strlen(trim($this->content));
        $this->gap = strlen($this->content) - strlen(rtrim($this->content));
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

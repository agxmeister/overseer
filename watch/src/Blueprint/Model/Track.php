<?php

namespace Watch\Blueprint\Model;

readonly class Track
{
    public int $duration;
    public int $gap;

    public function __construct(public string $content)
    {
        $this->duration = strlen(trim($this->content));
        $this->gap = strlen($this->content) - strlen(rtrim($this->content));
    }
}

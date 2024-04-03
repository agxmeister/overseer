<?php

namespace Watch\Description;

readonly class Track
{
    public function __construct(public string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

<?php

namespace Watch\Description;

readonly class Line
{
    public function __construct(public string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

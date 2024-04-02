<?php

namespace Watch\Description;

readonly class Line
{
    public LineType $type;

    public function __construct(public string $content)
    {
        $this->type = match (true) {
            str_contains($content, '|') => LineType::Issue,
            str_contains($content, '^') => LineType::Milestone,
            str_contains($content, '>') => LineType::Context,
            default => LineType::Undefined,
        };
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

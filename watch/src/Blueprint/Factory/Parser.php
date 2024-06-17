<?php

namespace Watch\Blueprint\Factory;

readonly class Parser
{
    public function __construct(private mixed $handler, private string $pattern, private array $defaults)
    {
    }

    public function getModels(Context $context): array
    {
        $handler = $this->handler;
        return array_map(
            fn($line) => $handler(
                new Line($line, $this->pattern, ...$this->defaults),
                $context,
            ),
            array_filter(
                $context->lines,
                fn($line) => preg_match($this->pattern, $line),
            ),
        );
    }
}

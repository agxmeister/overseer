<?php

namespace Watch;

/**
 * @property mixed $schedule
 * @property mixed $jira
 */
readonly class Config
{
    public function __construct(private object $data)
    {
    }

    public function __get($name): mixed
    {
        return $this->data->$name ?? null;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return array_reduce(
            explode('.', $name),
            fn($acc, string $part) => $acc?->$part ?? null,
            $this->data,
        ) ?? $default;
    }
}

<?php

namespace Watch\Schedule\Builder\Strategy\State;

use Watch\Config;
use Watch\Schedule\Builder\StateStrategy;

readonly class MapByStatus implements StateStrategy
{
    public function __construct(private Config $config)
    {
    }

    public function apply(array $attributes): array
    {
        return array_reduce(
            $this->config->jira->statuses,
            fn($acc, $status) => [
                ...$acc,
                $status->state => $status->name === $attributes['status']
            ],
            [],
        );
    }
}

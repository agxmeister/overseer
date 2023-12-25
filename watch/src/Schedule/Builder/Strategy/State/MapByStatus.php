<?php

namespace Watch\Schedule\Builder\Strategy\State;

use Watch\Schedule\Builder\StateStrategy;

readonly class MapByStatus implements StateStrategy
{

    public function apply(array $attributes): array
    {
        return [
            'started' => $attributes['status'] === 'In Progress',
            'completed' => $attributes['status'] === 'Done',
        ];
    }
}

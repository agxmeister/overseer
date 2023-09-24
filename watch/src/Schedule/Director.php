<?php

namespace Watch\Schedule;

use Watch\Schedule\Strategy\Strategy;

class Director
{
    public function __construct(private Builder $builder)
    {
    }

    public function create(array $issues, string $date, Strategy $strategy, Formatter $formatter): array
    {
        return $this->builder
            ->run($issues)
            ->schedule($strategy)
            ->release($formatter, $date);
    }
}

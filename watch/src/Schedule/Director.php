<?php

namespace Watch\Schedule;

use DateTime;
use Watch\Schedule\Strategy\Strategy;

class Director
{
    public function __construct(private Builder $builder)
    {
    }

    public function get(array $issues): array
    {
        return $this->builder
            ->run($issues)
            ->link()
            ->release();
    }

    public function create(array $issues, DateTime $date, Strategy $strategy): array
    {
        return $this->builder
            ->run($issues)
            ->distribute($strategy)
            ->schedule($date)
            ->link()
            ->release();
    }
}

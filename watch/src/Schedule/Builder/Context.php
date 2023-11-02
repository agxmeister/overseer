<?php

namespace Watch\Schedule\Builder;

readonly class Context
{
    public function __construct(private \DateTimeImmutable $now)
    {
    }

    public function getNow(): \DateTimeImmutable
    {
        return $this->now;
    }
}

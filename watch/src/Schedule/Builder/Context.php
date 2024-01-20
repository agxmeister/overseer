<?php

namespace Watch\Schedule\Builder;

readonly class Context
{
    public function __construct(public \DateTimeImmutable $now)
    {
    }
}

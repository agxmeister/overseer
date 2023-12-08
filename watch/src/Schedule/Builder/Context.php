<?php

namespace Watch\Schedule\Builder;

use Watch\Decorator\Factory;

readonly class Context
{
    public function __construct(public \DateTimeImmutable $now, public Factory $factory)
    {
    }
}

<?php

namespace Watch\Schedule\Builder;

use Watch\Subject\Adapter;

readonly class Context
{
    public function __construct(public \DateTimeImmutable $now, public Adapter $adapter)
    {
    }
}

<?php

namespace Watch\Blueprint\Builder\Asset;

use DateTimeImmutable;

readonly class Reference
{
    public function __construct(public int $offset, public ?DateTimeImmutable $date)
    {
    }
}

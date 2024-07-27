<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;

readonly class Reference
{
    public function __construct(public int $offset, public ?DateTimeImmutable $date)
    {
    }
}

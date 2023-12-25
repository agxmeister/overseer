<?php

namespace Watch\Schedule\Builder;

interface StateStrategy
{
    public function apply(array $attributes): array;
}

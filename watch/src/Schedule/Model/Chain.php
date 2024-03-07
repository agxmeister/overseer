<?php

namespace Watch\Schedule\Model;

readonly class Chain
{
    /**
     * @param Node[] $nodes
     */
    public function __construct(public array $nodes)
    {
    }
}

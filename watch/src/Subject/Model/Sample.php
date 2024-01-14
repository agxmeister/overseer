<?php

namespace Watch\Subject\Model;

readonly class Sample
{
    /**
     * @param Issue[] $issues
     * @param array $connectors
     */
    public function __construct(public array $issues, public array $connectors = [])
    {
    }
}

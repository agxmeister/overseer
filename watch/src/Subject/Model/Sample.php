<?php

namespace Watch\Subject\Model;

readonly class Sample
{
    /**
     * @param Issue[] $issues
     * @param Link[] $links
     */
    public function __construct(public array $issues, public array $links = [])
    {
    }
}

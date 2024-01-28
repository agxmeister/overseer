<?php

namespace Watch\Subject\Model;

readonly class Subject
{
    /**
     * @param Issue[] $issues
     * @param Link[] $links
     */
    public function __construct(public array $issues, public array $links = [])
    {
    }
}

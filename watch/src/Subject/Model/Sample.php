<?php

namespace Watch\Subject\Model;

readonly class Sample
{
    /**
     * @param Issue[] $issues
     * @param Joint[] $joints
     */
    public function __construct(public array $issues, public array $joints = [])
    {
    }
}

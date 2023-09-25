<?php

namespace Watch\Schedule\Model;

class Milestone extends Node
{
    public function __construct(string $name)
    {
        parent::__construct($name, 0);
    }
}

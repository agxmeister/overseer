<?php

namespace Watch\Schedule\Model;

abstract class Batch extends Node
{
    public function __construct(string $name)
    {
        parent::__construct($name, 0);
    }
}

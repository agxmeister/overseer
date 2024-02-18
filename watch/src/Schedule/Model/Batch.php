<?php

namespace Watch\Schedule\Model;

abstract class Batch extends Node
{
    public function __construct(string $name, array $attributes = [])
    {
        parent::__construct($name, 0, $attributes);
    }
}

<?php

namespace Watch\Schedule\Model;

abstract class Buffer extends Node
{
    const TYPE_PROJECT = 'project';
    const TYPE_MILESTONE = 'milestone';
    const TYPE_FEEDING = 'feeding';

    public function __construct(string $name, int $length, public readonly string $type, array $attributes = [])
    {
        parent::__construct($name, $length, $attributes);
    }
}

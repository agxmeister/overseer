<?php

namespace Watch\Schedule\Model;

class ProjectBuffer extends Buffer
{
    public function __construct(string $name, int $length, array $attributes = [])
    {
        parent::__construct($name, $length, self::TYPE_PROJECT, $attributes);
    }
}

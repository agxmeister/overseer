<?php

namespace Watch\Blueprint\Line;

readonly class Attribute
{
    public function __construct(public AttributeType $type, public string $value)
    {
    }
}

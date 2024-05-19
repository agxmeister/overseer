<?php

namespace Watch\Blueprint\Model;

readonly class Attribute
{
    public function __construct(public AttributeType $type, public string $value)
    {
    }
}

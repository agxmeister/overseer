<?php

namespace Watch\Description\Line;

readonly class Attribute
{
    public AttributeType $type;
    public string $value;

    public function __construct(public string $content)
    {
        list($code, $this->value) = explode(' ', $content);
        $this->type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
    }
}

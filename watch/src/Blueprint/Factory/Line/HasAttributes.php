<?php

namespace Watch\Blueprint\Factory\Line;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

trait HasAttributes
{
    public function getAttributes(string $content): array
    {
        return array_map(
            fn(string $attribute) => $this->getAttribute($attribute),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $content)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    public function getAttribute(string $content): Attribute
    {
        list($code, $value) = explode(' ', $content);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }
}

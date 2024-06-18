<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

trait HasAttributes
{
    private function getLineAttributes(string $content): array
    {
        return array_map(
            fn(string $attribute) => $this->getLineAttribute($attribute),
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

    private function getLineAttribute(string $content): Attribute
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

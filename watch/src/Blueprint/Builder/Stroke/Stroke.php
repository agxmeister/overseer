<?php

namespace Watch\Blueprint\Builder\Stroke;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

readonly class Stroke
{
    public array $parts;

    public function __construct(array $values, public string $attributes, public array $offsets, ...$defaults)
    {
        $this->parts = array_merge(
            $defaults,
            array_filter(
                $values,
                fn($value) => !is_null($value),
            ),
        );
    }

    public function getAttributes(): array
    {
        return array_map(
            fn(string $attribute) => $this->getAttribute($attribute),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $this->attributes)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    private function getAttribute(string $content): Attribute
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

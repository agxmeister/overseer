<?php

namespace Watch\Blueprint\Builder\Asset;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

readonly class Stroke
{
    public array $parts;

    public array $attributes;

    public function __construct(array $parts, public array $offsets, string $attributesContent, ...$defaults)
    {
        $this->parts = array_merge(
            $defaults,
            array_filter(
                $parts,
                fn($value) => !is_null($value),
            ),
        );
        $this->attributes = $this->getAttributes($attributesContent);
    }

    private function getAttributes(string $content): array
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

    private function getAttribute(string $content): Attribute
    {
        [$code, $value] = explode(' ', $content);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }
}

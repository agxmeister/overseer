<?php

namespace Watch\Description;

abstract readonly class Line
{
    /** @var Attribute[]  */
    public array $attributes;

    public function __construct(protected string $content)
    {
    }

    protected function getValuesByPattern(string $string, string $pattern, ...$defaults): array
    {
        $matches = [];
        preg_match($pattern, $string, $matches, PREG_UNMATCHED_AS_NULL);
        array_walk(
            $matches,
            fn(&$value, $key) => $value = $value ?? $defaults[$key] ?? null,
        );
        return array_filter(
            $matches,
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY,
        );
    }

    protected function setAttributes(string $attributesContent): void
    {
        $this->attributes = array_map(
            fn(string $attributeContent) => new Attribute($attributeContent),
            array_values(
                array_filter(
                    array_map(
                        fn($attributeContent) => trim($attributeContent),
                        explode(',', $attributesContent)
                    ),
                    fn(string $attributeContent) => !empty($attributeContent),
                )
            )
        );
    }
}

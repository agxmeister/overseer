<?php

namespace Watch\Description;

abstract readonly class Line
{
    /** @var Attribute[]  */
    public array $attributes;

    public function __construct(protected string $content)
    {
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

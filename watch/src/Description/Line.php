<?php

namespace Watch\Description;

use Watch\Description\Line\Attribute;

abstract readonly class Line
{
    /** @var Attribute[]  */
    public array $attributes;

    public function __construct(string $attributes = '')
    {
        $this->setAttributes($attributes);
    }

    protected function setAttributes(string $attributes): void
    {
        $this->attributes = array_map(
            fn(string $attribute) => new Attribute($attribute),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $attributes)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }
}

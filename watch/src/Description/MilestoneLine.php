<?php

namespace Watch\Description;

use DateTimeImmutable;

readonly class MilestoneLine extends Line
{
    /** @var Attribute[] */
    public array $attributes;

    public function __construct(public string $key, string $attributes, public int $markerOffset)
    {
        parent::__construct($attributes);
    }

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $this->attributes,
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }
}

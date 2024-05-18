<?php

namespace Watch\Blueprint\Line;

use DateTimeImmutable;

readonly class MilestoneLine extends Line
{
    public function __construct(public string $key, array $attributes)
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

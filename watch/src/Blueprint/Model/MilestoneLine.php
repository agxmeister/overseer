<?php

namespace Watch\Blueprint\Model;

use DateTimeImmutable;

readonly class MilestoneLine extends Model
{
    public function __construct(public string $key, public array $attributes)
    {
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

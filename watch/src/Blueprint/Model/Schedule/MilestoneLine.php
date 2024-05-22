<?php

namespace Watch\Blueprint\Model\Schedule;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Model;

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

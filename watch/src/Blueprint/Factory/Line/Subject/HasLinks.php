<?php

namespace Watch\Blueprint\Factory\Line\Subject;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Schedule\Mapper;

trait HasLinks
{
    public function getLinks(string $key, array $attributes, Mapper $mapper): array
    {
        return array_reduce(
            array_filter(
                $attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $key,
                    'to' => $attribute->value,
                    'type' => $attribute->type === AttributeType::Sequence
                        ? current($mapper->sequenceLinkTypes)
                        : current($mapper->scheduleLnkTypes),
                ],
            ],
            [],
        );
    }
}

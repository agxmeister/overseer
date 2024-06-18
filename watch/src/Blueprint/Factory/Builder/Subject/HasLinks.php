<?php

namespace Watch\Blueprint\Factory\Builder\Subject;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

trait HasLinks
{
    private function getLineLinks(string $key, array $attributes): array
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
                        ? current($this->mapper->sequenceLinkTypes)
                        : current($this->mapper->scheduleLnkTypes),
                ],
            ],
            [],
        );
    }
}

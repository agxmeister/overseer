<?php

namespace Watch\Blueprint\Line;

readonly abstract class TrackLine extends Line
{
    public function __construct(
        public string $key,
        public string $type,
        public Track $track,
        array $attributes,
    )
    {
        parent::__construct($attributes);
    }

    public function getLinks(): array
    {
        return array_reduce(
            array_filter(
                $this->attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $this->key,
                    'to' => $attribute->value,
                    'type' => $attribute->type === AttributeType::Sequence ? 'sequence' : 'schedule',
                ],
            ],
            [],
        );
    }
}

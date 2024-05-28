<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Blueprint as BlueprintModel;
use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Track;

abstract readonly class Blueprint
{
    abstract public function create(string $content): BlueprintModel;

    protected function getLines(string $content): array
    {
        return array_filter(
            explode("\n", $content),
            fn($line) => !empty(trim($line)),
        );
    }

    protected function getLineAttributes(string $content): array
    {
        return array_map(
            fn(string $attribute) => $this->getLineAttribute($attribute),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $content)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    abstract protected function getLineLinks(string $key, array $attributes): array;

    protected function getLineAttribute(string $content): Attribute
    {
        list($code, $value) = explode(' ', $content);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }

    protected function getTrack(string $content): Track
    {
        return new Track($content);
    }
}

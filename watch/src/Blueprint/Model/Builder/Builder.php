<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

abstract class Builder
{
    protected function getStrokeAttributes(array $attributes): array
    {
        return array_map(
            fn(string $attribute) => $this->getStrokeAttribute($attribute),
            $attributes,
        );
    }

    protected function getStrokeAttribute(string $attribute): Attribute
    {
        [$code, $value] = explode(' ', $attribute);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }

    abstract public function reset(): self;
    abstract public function release(): self;
    abstract public function setModel(Stroke $stroke): self;
}

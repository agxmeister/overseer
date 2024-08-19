<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Config;

abstract class Builder
{
    protected ?Reference $reference = null;

    public function __construct(protected Config $config)
    {
    }

    public function setReference(Drawing $drawing): self
    {
        $parser = new Parser($this->config->get('blueprint.drawing.stroke.pattern.reference'));
        $referenceStroke = $drawing->getStroke($parser);

        if (is_null($referenceStroke)) {
            $this->reference = null;
            return $this;
        }

        $this->reference = new Reference(
            $this->getReferenceMarkerOffset($referenceStroke),
            $this->getReferenceDate($referenceStroke),
        );

        return $this;
    }

    protected function getReferenceMarkerOffset(Stroke $referenceStroke): int
    {
        ['marker' => $markerOffset] = $referenceStroke->offsets;
        return $markerOffset;
    }

    protected function getReferenceDate(?Stroke $referenceStroke): ?DateTimeImmutable
    {
        ['attributes' => $attributes] = $referenceStroke->dashes;
        if (empty($attributes)) {
            return null;
        }

        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $this->getStrokeAttributes($attributes),
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }

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

    public function clean(): self
    {
        $this->reference = null;
        return $this;
    }

    abstract public function setModels(Drawing $drawing): self;
}

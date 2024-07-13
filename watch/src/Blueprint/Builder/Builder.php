<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Reference;
use Watch\Blueprint\Builder\Stroke\Parser;
use Watch\Blueprint\Builder\Stroke\Reference as ReferenceStroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

abstract class Builder
{
    protected ?Reference $reference = null;

    public function __construct(readonly protected Drawing $drawing)
    {
    }

    public function setReference(): self
    {
        $parser = new Parser(static::PATTERN_REFERENCE_STROKE);

        $referenceStroke = array_reduce(
            $parser->getMatches($this->drawing->strokes),
            fn($acc, $match) => new ReferenceStroke($match[0], $match[1]),
        );

        $this->reference = new Reference(
            $this->getReferenceMarkerOffset($referenceStroke),
            $this->getReferenceDate($referenceStroke),
        );

        return $this;
    }

    protected function getReferenceDate(?ReferenceStroke $referenceStroke): ?DateTimeImmutable
    {
        if (is_null($referenceStroke)) {
            return null;
        }

        list('attributes' => $attributes) = $referenceStroke->parts;
        if (empty($attributes)) {
            return null;
        }

        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $referenceStroke->getAttributes($attributes),
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }

    protected function getReferenceMarkerOffset(?ReferenceStroke $referenceStroke): int
    {
        if (is_null($referenceStroke)) {
            return 0;
        }
        ['marker' => $markerOffset] = $referenceStroke->offsets;
        return $markerOffset;
    }

    public function clean(): self
    {
        $this->reference = null;
        return $this;
    }

    abstract public function setModels(): self;
}

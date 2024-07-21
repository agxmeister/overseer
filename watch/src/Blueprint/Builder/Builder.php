<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Reference;
use Watch\Blueprint\Builder\Stroke\Parser;
use Watch\Blueprint\Builder\Stroke\Stroke;
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
        $referenceStroke = $this->drawing->getStroke($parser, 'attributes');

        $this->reference = new Reference(
            $this->getReferenceMarkerOffset($referenceStroke),
            $this->getReferenceDate($referenceStroke),
        );

        return $this;
    }

    protected function getReferenceDate(?Stroke $referenceStroke): ?DateTimeImmutable
    {
        if (is_null($referenceStroke)) {
            return null;
        }

        if (empty($referenceStroke->attributes)) {
            return null;
        }

        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $referenceStroke->attributes,
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }

    protected function getReferenceMarkerOffset(?Stroke $referenceStroke): int
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

<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

abstract class Builder
{
    protected ?Reference $reference = null;

    public function setReference(Drawing $drawing): self
    {
        $parser = new Parser(static::PATTERN_REFERENCE_STROKE);
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

    public function clean(): self
    {
        $this->reference = null;
        return $this;
    }

    abstract public function setModels(Drawing $drawing): self;
}

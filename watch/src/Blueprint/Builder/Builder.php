<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Line\Reference as ReferenceLine;

abstract class Builder
{
    protected ?Reference $reference = null;

    public function __construct(readonly protected Drawing $drawing)
    {
    }

    public function setReference(): self
    {
        $parser = new Parser(static::PATTERN_REFERENCE_LINE);

        $referenceStroke = array_reduce(
            $parser->getMatches($this->drawing->strokes),
            fn($acc, $match) => new ReferenceLine($match[0], $match[1]),
        );

        $this->reference = new Reference(
            $this->getReferenceMarkerOffset($referenceStroke),
            $this->getReferenceDate($referenceStroke),
        );

        return $this;
    }

    private function getReferenceDate(?ReferenceLine $referenceStroke): ?DateTimeImmutable
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

    private function getReferenceMarkerOffset(?ReferenceLine $referenceStroke): int
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

    abstract public function setNowDate(): self;
}

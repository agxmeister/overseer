<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Line\Reference as ReferenceLine;

abstract class Builder
{
    public function __construct(readonly protected Drawing $drawing, readonly protected Context $context)
    {
    }

    private function getReferenceDate(?ReferenceLine $contextLine): ?DateTimeImmutable
    {
        if (is_null($contextLine)) {
            return null;
        }
        list('attributes' => $attributes) = $contextLine->parts;
        if (empty($attributes)) {
            return null;
        }

        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $contextLine->getAttributes($attributes),
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }

    private function getReferenceMarkerOffset(?ReferenceLine $referenceLine): int
    {
        if (is_null($referenceLine)) {
            return 0;
        }
        ['marker' => $markerOffset] = $referenceLine->offsets;
        return $markerOffset;
    }

    public function parseReferenceData(): self
    {
        $parser = new Parser(static::PATTERN_REFERENCE_LINE);
        $referenceLine = array_reduce(
            $parser->getMatches($this->drawing->strokes),
            fn($acc, $match) => new ReferenceLine($match[0], $match[1]),
        );

        $this->context
            ->setReferenceDate($this->getReferenceDate($referenceLine))
            ->setReferenceMarkerOffset($this->getReferenceMarkerOffset($referenceLine));

        return $this;
    }

    public function clean(): self
    {
        $this->context->clean();
        return $this;
    }

    abstract public function setModels(): self;

    abstract public function setNowDate(): self;
}

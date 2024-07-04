<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Line\Reference as ReferenceLine;

abstract class Builder
{
    protected ?string $drawing = null;
    protected ?array $lines = null;
    protected ?Context $context = null;

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

    public function setDrawing(string $drawing): self
    {
        $this->drawing = $drawing;
        return $this;
    }

    public function setLines(): self
    {
        $this->lines = array_filter(
            explode("\n", $this->drawing),
            fn($line) => !empty(trim($line)),
        );
        return $this;
    }

    public function setContext(): self
    {
        $context = new Context();

        $parser = new Parser(static::PATTERN_REFERENCE_LINE);
        $referenceLine = array_reduce(
            $parser->getMatches($this->lines),
            fn($acc, $match) => new ReferenceLine($match[0], $match[1]),
        );

        $context
            ->setReferenceDate($this->getReferenceDate($referenceLine))
            ->setReferenceMarkerOffset($this->getReferenceMarkerOffset($referenceLine));

        $this->context = $context;
        return $this;
    }

    public function clean(): self
    {
        $this->drawing = null;
        $this->lines = null;
        $this->context = null;
        return $this;
    }

    abstract public function setContent(): self;
}

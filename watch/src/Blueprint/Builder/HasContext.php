<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Line\Reference as ReferenceLine;

trait HasContext
{
    /**
     * @param string[] $lines
     * @param string $referenceLinePattern
     * @return Context
     */
    private function getContext(array $lines, string $referenceLinePattern): Context
    {
        $context = new Context();

        $parser = new Parser($referenceLinePattern);
        $referenceLine = array_reduce(
            $parser->getMatches($lines),
            fn($acc, $match) => new ReferenceLine($match[0], $match[1]),
        );

        $context
            ->setLines($lines)
            ->setReferenceDate($this->getReferenceDate($referenceLine))
            ->setReferenceMarkerOffset($this->getReferenceMarkerOffset($referenceLine));

        return $context;
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
}

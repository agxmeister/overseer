<?php

namespace Watch\Blueprint\Factory;

use DateTimeImmutable;
use Watch\Blueprint\Factory\Line\Line;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

trait HasContext
{
    use HasLines;

    private function getContext($content, $pattern): Context
    {
        $lines = $this->getLines($content);

        $contextLine = array_reduce(
            array_filter(
                $lines,
                fn($line) => preg_match($pattern, $line),
            ),
            fn($acc, $line) => new Line($line, $pattern),
        );

        $context = new Context($lines, $this->getContextDate($contextLine));

        if (!is_null($contextLine)) {
            list('marker' => $markerOffset) = $contextLine->offsets;
            $context->setContextMarkerOffset($markerOffset);
        }

        return $context;
    }

    private function getContextDate($contextLine): ?DateTimeImmutable
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
                    $this->getLineAttributes($attributes),
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }
}

<?php

namespace Watch\Blueprint\Factory;

use DateTimeImmutable;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Factory\Line\Context as ContextLine;

trait HasContext
{
    use HasLines;

    private function getContext($content, $pattern): Context
    {
        $lines = $this->getLines($content);

        $parser = new Parser($pattern);

        $contextLine = array_reduce(
            $parser->getMatches($lines),
            fn($acc, $match) => new ContextLine($match[0], $match[1]),
        );

        $context = new Context($lines, $this->getContextDate($contextLine));

        if (!is_null($contextLine)) {
            list('marker' => $markerOffset) = $contextLine->offsets;
            $context->setContextMarkerOffset($markerOffset);
        }

        return $context;
    }

    private function getContextDate(?ContextLine $contextLine): ?DateTimeImmutable
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
}

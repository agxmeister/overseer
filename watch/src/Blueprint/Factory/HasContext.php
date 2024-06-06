<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;

trait HasContext
{
    use HasLines;

    private function getContext($content, $pattern): Context
    {
        $context = new Context();

        $contextLine = array_reduce(
            array_filter(
                $this->getLines($content),
                fn($line) => preg_match($pattern, $line),
            ),
            fn($acc, $line) => $line,
        );

        if (!is_null($contextLine)) {
            $offsets = [];
            Utils::getStringParts($contextLine, $pattern, $offsets);
            list('marker' => $markerOffset) = $offsets;
            $context->setContextMarkerOffset($markerOffset);
        }

        return $context;
    }
}

<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;

trait HasContext
{
    use HasLines;

    private function getContext($content, $pattern): Context
    {
        $lines = $this->getLines($content);
        $context = new Context($lines);

        $contextLine = array_reduce(
            array_filter(
                $lines,
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

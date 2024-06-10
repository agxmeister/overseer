<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Factory\Context\Context;

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

        $context = new Context($lines);

        if (!is_null($contextLine)) {
            list('marker' => $markerOffset) = $contextLine->offsets;
            $context->setContextMarkerOffset($markerOffset);
        }

        return $context;
    }
}

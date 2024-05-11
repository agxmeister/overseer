<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Blueprint as BlueprintModel;
use Watch\Blueprint\Line\Line;

abstract readonly class Blueprint
{
    abstract public function create(string $content): BlueprintModel;

    /**
     * @param string $content
     * @return Line[]
     */
    protected function getLines(string $content): array
    {
        $contents = array_filter(
            explode("\n", $content),
            fn($line) => !empty(trim($line)),
        );
        return array_values(
            array_filter(
                array_map(
                    fn(string $content) => $this->getLine($content),
                    $contents,
                ),
                fn(Line|null $line) => !is_null($line),
            )
        );
    }

    abstract protected function getLine(string $content): ?Line;
}

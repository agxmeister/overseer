<?php

namespace Watch\Blueprint\Builder\Asset;

/**
 * @property $dashes
 * @property $offsets
 */
readonly class Stroke
{
    /**
     * @param Dash[] $dashes
     */
    public function __construct(public array $dashes)
    {
    }

    public function __get($name)
    {
        return match($name) {
            'offsets' => array_map(
                fn(?Dash $dash) => $dash?->offset,
                $this->dashes,
            ),
        };
    }
}

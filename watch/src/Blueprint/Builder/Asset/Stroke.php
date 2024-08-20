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
    public function __construct(private array $dashes)
    {
    }

    public function __get($name)
    {
        return match($name) {
            'dashes' => array_map(
                fn(Dash $dash) => $dash->value,
                $this->dashes,
            ),
            'offsets' => array_map(
                fn(Dash $dash) => $dash->offset,
                $this->dashes,
            ),
        };
    }
}

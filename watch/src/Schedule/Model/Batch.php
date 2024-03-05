<?php

namespace Watch\Schedule\Model;

abstract class Batch extends Node
{
    public function __construct(string $name, array $attributes = [])
    {
        parent::__construct($name, 0, $attributes);
    }

    public function getLength(bool $withPreceders = false, array $types = []): int
    {
        if (!$withPreceders) {
            return 0;
        }
        return max(array_map(
            fn(Node $node) => $node->getLength($withPreceders, $types),
            $this->getPreceders(),
        ));
    }
}

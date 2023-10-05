<?php

namespace Watch\Schedule\Model;

class Link
{
    const TYPE_SEQUENCE = 'sequence';
    const TYPE_RESOURCE = 'resource';
    const TYPE_SCHEDULE = 'schedule';

    public function __construct(private readonly Node $node, private readonly string $type = self::TYPE_SEQUENCE)
    {
    }

    public function getNode(): Node
    {
        return $this->node;
    }

    public function getType(): string
    {
        return $this->type;
    }
}

<?php

namespace Watch\Schedule;

class Link
{
    const TYPE_SEQUENCE = 'sequence';
    const TYPE_RESOURCE = 'resource';
    const TYPE_SCHEDULE = 'schedule';

    public function __construct(private Node $node, private string $type = self::TYPE_SEQUENCE)
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

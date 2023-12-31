<?php

namespace Watch\Schedule\Model;

use Watch\Decorator\Link as LinkDecorator;

readonly class Link implements LinkDecorator
{
    const TYPE_SEQUENCE = 'sequence';
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

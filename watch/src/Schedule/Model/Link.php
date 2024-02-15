<?php

namespace Watch\Schedule\Model;

readonly class Link
{
    const TYPE_SEQUENCE = 'sequence';
    const TYPE_SCHEDULE = 'schedule';
    const TYPE_UNKNOWN = 'unknown';

    public function __construct(public Node $node, public string $type = self::TYPE_SEQUENCE)
    {
    }
}

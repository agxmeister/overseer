<?php

namespace Watch\Subject\Model;

use Watch\Decorator\Link as LinkDecorator;

readonly class Link implements LinkDecorator
{
    const ROLE_OUTWARD = 'outward';
    const ROLE_INWARD = 'inward';
    public function __construct(public int $id, public string $key, public string $type, public string $role)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }
}

<?php

namespace Watch\Subject\Model;

use Watch\Decorator\Link as LinkDecorator;

readonly class Link implements LinkDecorator
{
    const ROLE_OUTWARD = 'outward';
    const ROLE_INWARD = 'inward';

    public function __construct(
        public int|null    $id = null,
        public string|null $key = null,
        public string|null $type = null,
        public string|null $role = null,
    )
    {
    }

    public function getType(): string
    {
        return $this->type;
    }
}

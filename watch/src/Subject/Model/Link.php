<?php

namespace Watch\Subject\Model;

readonly class Link
{
    const ROLE_OUTWARD = 'outward';
    const ROLE_INWARD = 'inward';
    public function __construct(public int $id, public string $key, public string $type, public string $role)
    {
    }
}

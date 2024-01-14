<?php

namespace Watch\Subject\Model;

readonly class Joint
{
    public function __construct(public string $id, public string $from, public string $to, public string $type)
    {
    }
}

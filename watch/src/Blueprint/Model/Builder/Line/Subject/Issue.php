<?php

namespace Watch\Blueprint\Model\Builder\Line\Subject;

use Watch\Blueprint\Model\Builder\Line\HasAttributes;
use Watch\Blueprint\Model\Builder\Line\Line;

readonly class Issue extends Line
{
    use HasAttributes, HasLinks;
}

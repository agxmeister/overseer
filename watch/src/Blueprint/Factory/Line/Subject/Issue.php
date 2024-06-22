<?php

namespace Watch\Blueprint\Factory\Line\Subject;

use Watch\Blueprint\Factory\Line\HasAttributes;
use Watch\Blueprint\Factory\Line\Line;

readonly class Issue extends Line
{
    use HasAttributes, HasLinks;
}

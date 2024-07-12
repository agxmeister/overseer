<?php

namespace Watch\Blueprint\Model\Builder\Stroke\Subject;

use Watch\Blueprint\Model\Builder\Stroke\HasAttributes;
use Watch\Blueprint\Model\Builder\Stroke\Stroke;

readonly class Issue extends Stroke
{
    use HasAttributes, HasLinks;
}

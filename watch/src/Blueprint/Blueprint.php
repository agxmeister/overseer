<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Milestone;

readonly abstract class Blueprint
{
    abstract protected function getProject(): Milestone|null;
}

<?php

namespace Watch\Blueprint\Factory\Model;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Factory\Line;

interface Builder
{
    public function reset(): Builder;
    public function release(): Builder;
    public function setModel(Line $line, Context $context): Builder;
}

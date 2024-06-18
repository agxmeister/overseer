<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Factory\Context;
use Watch\Blueprint\Factory\Line;

interface Builder
{
    public function reset(): Builder;
    public function release(): Builder;
    public function setContext(Context $context): Builder;
    public function setModel(Line $line): Builder;
}

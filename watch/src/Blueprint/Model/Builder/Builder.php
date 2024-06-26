<?php

namespace Watch\Blueprint\Model\Builder;

use Watch\Blueprint\Builder\Context;

interface Builder
{
    public function reset(): Builder;
    public function release(): Builder;
    public function setContext(Context $context): Builder;
    public function setModel(array $values, array $offsets, ...$defaults): Builder;
}

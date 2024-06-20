<?php

namespace Watch\Blueprint\Factory\Builder;

use Watch\Blueprint\Factory\Context;

interface Builder
{
    public function reset(): Builder;
    public function release(): Builder;
    public function setContext(Context $context): Builder;
    public function setModel(string $content, string $pattern, ...$defaults): Builder;
}

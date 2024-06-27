<?php

namespace Watch\Blueprint\Builder;

interface Builder
{
    public function clean(): self;
    public function setContent(string $content): self;
}

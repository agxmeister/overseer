<?php

namespace Watch\Decorator;

interface Factory
{
    public function getLink(Link $link): Link;
}

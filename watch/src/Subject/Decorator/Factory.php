<?php

namespace Watch\Subject\Decorator;

use Watch\Decorator\Factory as DecoratorFactory;
use Watch\Decorator\Link as LinkDecorator;

class Factory implements DecoratorFactory
{
    public function getLink(LinkDecorator $link): Link
    {
        return new Link($link);
    }
}

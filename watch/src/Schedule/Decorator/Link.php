<?php

namespace Watch\Schedule\Decorator;

use Watch\Decorator\Link as LinkDecorator;
use Watch\Schedule\Model\Link as ScheduleLink;

readonly class Link implements LinkDecorator
{
    public function __construct(private LinkDecorator $link)
    {
    }

    public function getType(): string
    {
        return $this->link->getType() === ScheduleLink::TYPE_SEQUENCE ? 'Depends' : 'Follows';
    }
}

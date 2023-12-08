<?php

namespace Watch\Subject\Decorator;

use Watch\Decorator\Link as LinkDecorator;
use Watch\Schedule\Model\Link as ScheduleLink;

readonly class Link implements LinkDecorator
{
    public function __construct(private LinkDecorator $link)
    {
    }

    public function getType(): string
    {
        return $this->link->getType() === 'Depends' ? ScheduleLink::TYPE_SEQUENCE : ScheduleLink::TYPE_SCHEDULE;
    }
}

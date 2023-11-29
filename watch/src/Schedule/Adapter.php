<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Link as ScheduleLink;

class Adapter
{
    public function getSubjectLinkTypeByScheduleLinkType(string $type): string
    {
        return $type === ScheduleLink::TYPE_SEQUENCE ? 'Depends' : 'Follows';
    }
}

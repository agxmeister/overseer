<?php

namespace Watch\Subject;

use Watch\Subject\Model\Link as SubjectLink;
use Watch\Schedule\Model\Link as ScheduleLink;

class Adapter
{
    public function getScheduleLinkTypeBySubjectLink(SubjectLink $link): string
    {
        return $link->type === 'Depends' ? ScheduleLink::TYPE_SEQUENCE : ScheduleLink::TYPE_SCHEDULE;
    }
}

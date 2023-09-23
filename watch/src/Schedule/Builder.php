<?php

namespace Watch\Schedule;

use Watch\Schedule\Strategy\Strategy;

class Builder
{
    public function __construct(private Formatter $formatter)
    {
    }

    public function getSchedule(array $issues, string $date, Strategy $strategy): array
    {
        $milestone = Utils::getMilestone($issues);
        $strategy->schedule($milestone);
        return $this->formatter->getSchedule($issues, $milestone, $date);
    }
}

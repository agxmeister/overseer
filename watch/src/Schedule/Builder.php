<?php

namespace Watch\Schedule;

use Watch\Schedule\Model\Milestone;
use Watch\Schedule\Strategy\Strategy;

class Builder
{
    private array|null $issues = null;
    private Milestone|null $milestone = null;

    public function run(array $issues): self
    {
        $this->issues = $issues;
        $this->milestone = Utils::getMilestone($issues);
        return $this;
    }

    public function schedule(Strategy $strategy): self
    {
        $strategy->schedule($this->milestone);
        return $this;
    }

    public function release(Formatter $formatter, $date): array
    {
        return $formatter->getSchedule($this->issues, $this->milestone, $date);
    }
}

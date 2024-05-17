<?php

namespace Watch\Blueprint\Factory\Context;

class Context
{
    private int $issuesEndPosition = 0;
    private int $projectMarkerOffset = 0;

    public function setIssuesEndPosition($issuesEndPosition): void
    {
        $this->issuesEndPosition = $issuesEndPosition;
    }

    public function getIssuesEndPosition(): int
    {
        return $this->issuesEndPosition;
    }

    public function setProjectMarkerOffset($projectMarkerOffset): void
    {
        $this->projectMarkerOffset = $projectMarkerOffset;
    }

    public function getProjectMarkerOffset(): int
    {
        return $this->projectMarkerOffset;
    }
}

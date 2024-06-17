<?php

namespace Watch\Blueprint\Factory;

use DateTimeImmutable;

class Context
{
    private int $projectMarkerOffset = 0;
    private int $contextMarkerOffset = 0;
    private int $issuesEndPosition = 0;

    public function __construct(public readonly array $lines, public readonly ?DateTimeImmutable $date)
    {
    }

    public function setProjectMarkerOffset($projectMarkerOffset): void
    {
        $this->projectMarkerOffset = $projectMarkerOffset;
    }

    public function getProjectMarkerOffset(): int
    {
        return $this->projectMarkerOffset;
    }

    public function setContextMarkerOffset($contextMarkerOffset): void
    {
        $this->contextMarkerOffset = $contextMarkerOffset;
    }

    public function getContextMarkerOffset(): int
    {
        return $this->contextMarkerOffset;
    }

    public function setIssuesEndPosition($issuesEndPosition): void
    {
        $this->issuesEndPosition = $issuesEndPosition;
    }

    public function getIssuesEndPosition(): int
    {
        return $this->issuesEndPosition;
    }
}

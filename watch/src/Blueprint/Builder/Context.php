<?php

namespace Watch\Blueprint\Builder;

use DateTimeImmutable;

class Context
{
    public ?DateTimeImmutable $referenceDate = null;
    private int $referenceMarkerOffset = 0;
    private int $projectMarkerOffset = 0;
    private int $issuesEndPosition = 0;

    public function clean()
    {
        $this->referenceDate = null;
        $this->referenceMarkerOffset = 0;
        $this->projectMarkerOffset = 0;
        $this->issuesEndPosition = 0;
    }

    public function setReferenceDate(?DateTimeImmutable $referenceDate): self
    {
        $this->referenceDate = $referenceDate;
        return $this;
    }

    public function setReferenceMarkerOffset(int $referenceMarkerOffset): self
    {
        $this->referenceMarkerOffset = $referenceMarkerOffset;
        return $this;
    }

    public function setProjectMarkerOffset($projectMarkerOffset): void
    {
        $this->projectMarkerOffset = $projectMarkerOffset;
    }

    public function getProjectMarkerOffset(): int
    {
        return $this->projectMarkerOffset;
    }

    public function getReferenceMarkerOffset(): int
    {
        return $this->referenceMarkerOffset;
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

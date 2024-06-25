<?php

namespace Watch\Blueprint\Factory;

use DateTimeImmutable;

class Context
{
    /** @var string[] */
    public array $lines = [];
    public ?DateTimeImmutable $referenceDate = null;
    private int $referenceMarkerOffset = 0;
    private int $projectMarkerOffset = 0;
    private int $issuesEndPosition = 0;

    /**
     * @param string[] $lines
     * @return $this
     */
    public function setLines(array $lines): self
    {
        $this->lines = $lines;
        return $this;
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

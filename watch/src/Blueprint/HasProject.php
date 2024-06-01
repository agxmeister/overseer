<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Track;

trait HasProject
{
    use HasTracks, HasMilestones;

    public function getProjectBeginDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers
            ? $this->getProject()?->getDate()?->modify("-{$projectLength} day")
            : $this->getProject()?->getDate();
    }

    public function getProjectEndDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers
            ? $this->getProject()?->getDate()
            : $this->getProject()?->getDate()?->modify("{$projectLength} day");
    }

    public function getProjectLength(): int
    {
        $tracks = $this->getTracks();
        return
            array_reduce(
                $tracks,
                fn($acc, Track $track) => max($acc, strlen($track->content)),
                0
            ) -
            array_reduce(
                $tracks,
                fn($acc, Track $track) => min($acc, strlen($track->content) - strlen(rtrim($track->content))),
                PHP_INT_MAX
            ) -
            array_reduce(
                $tracks,
                fn($acc, Track $track) => min($acc, strlen($track->content) - strlen(ltrim($track->content))),
                PHP_INT_MAX
            );
    }

    public function getProjectName(): string
    {
        return $this->getProject()?->key;
    }

    protected function getProjectBeginGap(): int
    {
        return array_reduce(
            $this->getTracks(),
            fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
            PHP_INT_MAX
        );
    }

    protected function getProjectEndGap(): int
    {
        return array_reduce(
            $this->getTracks(),
            fn($acc, Track $track) => min($acc, strlen($track->content) - strlen(rtrim($track->content))),
            PHP_INT_MAX
        );
    }

    protected function getProject(): Milestone|null
    {
        return array_reduce(
            array_filter(
                $this->milestones,
                fn($line) => $line instanceof Milestone,
            ),
            fn($acc, $line) => $line,
        );
    }
}

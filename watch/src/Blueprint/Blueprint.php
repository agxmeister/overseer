<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Issue;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Model\WithTrack;

readonly abstract class Blueprint
{
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

    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn(WithTrack $line) => $line->track,
            array_values(
                array_filter(
                    $this->getModels(),
                    fn($line) => $line instanceof WithTrack,
                ),
            ),
        );
    }

    abstract protected function getProject(): Milestone|null;
}

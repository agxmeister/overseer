<?php

namespace Watch\Blueprint;

use DateTimeImmutable;
use Watch\Blueprint\Model\Schedule\Issue;
use Watch\Blueprint\Model\Schedule\Milestone;
use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Model\WithTrack;

readonly abstract class Blueprint
{
    /**
     * @return array[]
     */
    public function getMilestones(): array
    {
        $milestones = array_map(
            fn(Milestone $line) => [
                'key' => $line->key,
                'date' => $line->getDate(),
            ],
            $this->getMilestoneLines(),
        );
        usort($milestones, fn($a, $b) => $a['date'] < $b['date'] ? -1 : ($a['date'] > $b['date'] ? 1 : 0));

        for ($i = 0; $i < sizeof($milestones); $i++) {
            $milestones[$i]['begin'] = ($this->isEndMarkers
                ? (
                $i > 0
                    ? $milestones[$i - 1]['date']
                    : $this->getProjectBeginDate()
                )
                : $milestones[$i]['date'])->format('Y-m-d');
            $milestones[$i]['end'] = ($this->isEndMarkers
                ? $milestones[$i]['date']
                : (
                $i < sizeof($milestones) - 1
                    ? $milestones[$i + 1]['date']
                    : $this->getProjectEndDate()
                ))->format('Y-m-d');
        }

        return array_map(
            fn($milestone) => array_filter(
                (array)$milestone,
                fn($key) => in_array($key, ['key', 'begin', 'end']),
                ARRAY_FILTER_USE_KEY,
            ),
            [
                ...$milestones,
                [
                    'key' => $this->getProjectLine()->key,
                    'begin' => $this->getProjectBeginDate()->format('Y-m-d'),
                    'end' => $this->getProjectEndDate()->format('Y-m-d'),
                ]
            ],
        );
    }

    /**
     * @return string[]
     */
    public function getMilestoneNames(): array
    {
        return array_map(
            fn(Milestone $milestone) => $milestone->key,
            $this->getMilestoneLines()
        );
    }

    public function getProjectName(): string
    {
        return $this->getProjectLine()?->key;
    }

    public function getProjectBeginDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers
            ? $this->getProjectLine()?->getDate()?->modify("-{$projectLength} day")
            : $this->getProjectLine()?->getDate();
    }

    public function getProjectEndDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers
            ? $this->getProjectLine()?->getDate()
            : $this->getProjectLine()?->getDate()?->modify("{$projectLength} day");
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
     * @return Issue[]
     */
    protected function getTrackLines(): array
    {
        return array_values(
            array_filter(
                $this->getModels(),
                fn($line) => $line instanceof WithTrack,
            ),
        );
    }

    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn(WithTrack $line) => $line->track,
            $this->getTrackLines(),
        );
    }

    /**
     * @return Milestone[]
     */
    protected function getMilestoneLines(): array
    {
        return array_slice(array_values(
            array_filter(
                $this->getModels(),
                fn($line) => get_class($line) === Milestone::class,
            )
        ), 0, -1);
    }

    protected function getProjectLine(): Milestone|null
    {
        return array_reduce(
            array_filter(
                $this->getModels(),
                fn($line) => $line instanceof Milestone,
            ),
            fn($acc, $line) => $line,
        );
    }

    abstract protected function getModels(): array;
}

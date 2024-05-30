<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Schedule\Milestone;

trait HasMilestones
{
    /**
     * @return array[]
     */
    public function getMilestonesData(): array
    {
        $milestones = array_map(
            fn(Milestone $line) => [
                'key' => $line->key,
                'date' => $line->getDate(),
            ],
            $this->getMilestones(),
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
                    'key' => $this->getProject()->key,
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
            $this->getMilestones()
        );
    }

    public function getProjectName(): string
    {
        return $this->getProject()?->key;
    }

    /**
     * @return Milestone[]
     */
    protected function getMilestones(): array
    {
        return array_slice(array_values(
            array_filter(
                $this->milestones,
                fn($line) => get_class($line) === Milestone::class,
            )
        ), 0, -1);
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

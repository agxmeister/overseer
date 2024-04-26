<?php

namespace Watch;

use Watch\Description\Attribute;
use Watch\Description\AttributeType;
use Watch\Description\ContextLine;
use Watch\Description\ScheduleIssueLine;
use Watch\Description\Line;
use Watch\Description\MilestoneLine;
use Watch\Description\Track;
use Watch\Description\TrackLine;
use Watch\Schedule\Mapper;

abstract class Description
{
    /** @var Line[] */
    protected array|null $lines = null;

    public function __construct(readonly protected string $description)
    {
    }

    /**
     * @return array[]
     */
    public function getMilestones(): array
    {
        $milestones = array_map(
            fn(MilestoneLine $line) => [
                'key' => $line->key,
                'date' => $line->getDate(),
            ],
            $this->getMilestoneLines(),
        );
        usort($milestones, fn($a, $b) => $a['date'] < $b['date'] ? -1 : ($a['date'] > $b['date'] ? 1 : 0));

        $isEndMarkers = $this->isEndMarkers();
        for ($i = 0; $i < sizeof($milestones); $i++) {
            $milestones[$i]['begin'] = ($isEndMarkers
                ? (
                $i > 0
                    ? $milestones[$i - 1]['date']
                    : $this->getProjectBeginDate()
                )
                : $milestones[$i]['date'])->format('Y-m-d');
            $milestones[$i]['end'] = ($isEndMarkers
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
            fn(MilestoneLine $milestone) => $milestone->key,
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
        return $this->isEndMarkers()
            ? $this->getProjectLine()?->getDate()?->modify("-{$projectLength} day")
            : $this->getProjectLine()?->getDate();
    }

    public function getProjectEndDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers()
            ? $this->getProjectLine()?->getDate()
            : $this->getProjectLine()?->getDate()?->modify("{$projectLength} day");
    }

    public function getNowDate(): \DateTimeImmutable|null
    {
        $projectLine = $this->getProjectLine();
        if (is_null($projectLine)) {
            return null;
        }
        $contextLine = $this->getContextLine();
        if (is_null($contextLine)) {
            return $this->getProjectBeginDate();
        }
        $gap = $contextLine->getMarkerPosition() - $projectLine->getMarkerPosition();
        return $projectLine->getDate()->modify("{$gap} day");
    }

    public function getProjectLength(): int
    {
        $tracks = $this->getTracks();
        return
            array_reduce(
                $tracks,
                fn($acc, $track) => max($acc, strlen($track)),
                0
            ) -
            array_reduce(
                $tracks,
                fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
                PHP_INT_MAX
            ) -
            array_reduce(
                $tracks,
                fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
                PHP_INT_MAX
            );
    }

    /**
     * @param string $from
     * @param Attribute[] $attributes
     * @param Mapper|null $mapper
     * @return array
     */
    protected function getLinksByAttributes(string $from, array $attributes, Mapper $mapper = null): array
    {
        return array_reduce(
            array_filter(
                $attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $from,
                    'to' => $attribute->value,
                    'type' => !is_null($mapper)
                        ? ($attribute->type === AttributeType::Sequence ? current($mapper->sequenceLinkTypes) : current($mapper->scheduleLnkTypes))
                        : ($attribute->type === AttributeType::Sequence ? 'sequence' : 'schedule'),
                ],
            ],
            [],
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
            fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
            PHP_INT_MAX
        );
    }

    protected function isEndMarkers(): bool
    {
        return $this->getProjectLine()?->getMarkerPosition() >= max(
            array_map(
                fn(TrackLine $issueLine) => $issueLine->getEndPosition(),
                $this->getTrackLines(),
            )
        );
    }

    /**
     * @return ScheduleIssueLine[]
     */
    protected function getTrackLines(): array
    {
        return array_values(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof TrackLine,
            ),
        );
    }

    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn(TrackLine $line) => $line->track,
            $this->getTrackLines(),
        );
    }

    /**
     * @return MilestoneLine[]
     */
    protected function getMilestoneLines(): array
    {
        return array_slice(array_values(
            array_filter(
                $this->getLines(),
                fn(Line $line) => get_class($line) === MilestoneLine::class,
            )
        ), 0, -1);
    }

    protected function getProjectLine(): MilestoneLine|null
    {
        return array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof MilestoneLine,
            ),
            fn($acc, $line) => $line,
        );
    }

    protected function getContextLine(): ContextLine|null
    {
        return array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof ContextLine,
            ),
            fn($acc, $line) => $line,
        );
    }

    /**
     * @return Line[]
     */
    protected function getLines(): array
    {
        if (!is_null($this->lines)) {
            return $this->lines;
        }
        $contents = array_filter(
            explode("\n", $this->description),
            fn($line) => !empty(trim($line)),
        );
        $this->lines = array_values(
            array_filter(
                array_map(
                    fn(string $content) => $this->getLine($content),
                    $contents,
                ),
                fn(Line|null $line) => !is_null($line),
            )
        );
        return $this->lines;
    }

    protected abstract function getLine(string $content): Line;
}

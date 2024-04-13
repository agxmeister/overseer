<?php

namespace Watch;

use Watch\Description\Attribute;
use Watch\Description\AttributeType;
use Watch\Description\BufferLine;
use Watch\Description\ContextLine;
use Watch\Description\IssueLine;
use Watch\Description\Line;
use Watch\Description\MilestoneLine;
use Watch\Description\ProjectLine;
use Watch\Description\Track;
use Watch\Description\TrackLine;
use Watch\Schedule\Mapper;

class Description
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
     * @return IssueLine[]
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
        return array_values(
            array_filter(
                $this->getLines(),
                fn(Line $line) => get_class($line) === MilestoneLine::class,
            )
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

    protected function getProjectLine(): ProjectLine|null
    {
        return array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof ProjectLine,
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
        $projectLineExists = str_contains($contents[array_key_last($contents)], '^');
        $this->lines = array_values(
            array_filter(
                array_map(
                    fn(string $content) => match (true) {
                        str_contains($content, '|') => match (1) {
                            preg_match('|[x*.]+|', $content) => new IssueLine($content),
                            preg_match('|[_!]+|', $content) => new BufferLine($content),
                            default => null,
                        },
                        str_contains($content, '^') => new MilestoneLine($content),
                        str_contains($content, '>') => new ContextLine($content),
                        default => null,
                    },
                    $projectLineExists ? array_slice($contents, 0, -1) : $contents,
                ),
                fn(Line|null $line) => !is_null($line),
            )
        );
        if ($projectLineExists) {
            $this->lines[] = new ProjectLine($contents[array_key_last($contents)]);
        }
        return $this->lines;
    }
}

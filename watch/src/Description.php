<?php

namespace Watch;

use Watch\Description\IssueLine;
use Watch\Description\Line;
use Watch\Description\LineType;
use Watch\Description\Track;
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
            fn($line) => [
                'key' => self::getMilestoneKey($line),
                'date' => self::getMilestoneDate($line),
            ],
            self::getMilestoneLines(),
        );
        usort($milestones, fn($a, $b) => $a['date'] < $b['date'] ? -1 : ($a['date'] > $b['date'] ? 1 : 0));

        $isEndMarkers = self::isEndMarkers();
        for ($i = 0; $i < sizeof($milestones); $i++) {
            $milestones[$i]['begin'] = ($isEndMarkers
                ? (
                $i > 0
                    ? $milestones[$i - 1]['date']
                    : self::getProjectBeginDate()
                )
                : $milestones[$i]['date'])->format('Y-m-d');
            $milestones[$i]['end'] = ($isEndMarkers
                ? $milestones[$i]['date']
                : (
                $i < sizeof($milestones) - 1
                    ? $milestones[$i + 1]['date']
                    : self::getProjectEndDate()
                ))->format('Y-m-d');
        }

        return array_map(
            fn($milestone) => array_filter(
                (array)$milestone,
                fn($key) => in_array($key, ['key', 'begin', 'end']),
                ARRAY_FILTER_USE_KEY,
            ),
            $milestones,
        );
    }

    /**
     * @return string[]
     */
    public function getMilestoneNames(): array
    {
        return array_slice(array_map(
            fn($milestone) => $milestone['key'],
            $this->getMilestones()
        ), 0, -1);
    }

    public function getProjectName(): string
    {
        return current(array_reverse(array_map(
            fn($milestone) => $milestone['key'],
            $this->getMilestones()
        )));
    }

    public function getProjectBeginDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers()
            ? $this->getProjectDate()?->modify("-{$projectLength} day")
            : $this->getProjectDate();
    }

    public function getProjectEndDate(): \DateTimeImmutable|null
    {
        $projectLength = $this->getProjectLength();
        return $this->isEndMarkers()
            ? $this->getProjectDate()
            : $this->getProjectDate()?->modify("{$projectLength} day");
    }

    public function getNowDate(): \DateTimeImmutable|null
    {
        $milestoneLines = $this->getMilestoneLines();
        if (empty($milestoneLines)) {
            return null;
        }
        $contextLine = $this->getContextLine();
        if (is_null($contextLine)) {
            return $this->getProjectBeginDate();
        }
        $milestoneLine = current($milestoneLines);
        $gap = strpos($contextLine, '>') - strpos($milestoneLine, '^');
        return $this->getMilestoneDate($milestoneLine)->modify("{$gap} day");
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

    protected function getIssueComponents(string $line): array
    {
        $data = explode('|', $line);
        return [
            'name' => trim(rtrim($data[0], '~+')),
            'duration' => strlen(trim($data[1])),
            'started' => str_ends_with($data[0], '~'),
            'completed' => str_ends_with($data[0], '+'),
            'scheduled' => in_array(trim($data[1])[0], ['*']),
            'gap' => strlen($data[1]) - strlen(rtrim($data[1])),
        ];
    }

    protected function getNameComponents(string $name): array
    {
        return array_map(
            fn($name, $value) => $value ?? match($name) {
                'project' => 'PRJ',
                'type' => 'T',
                default => null,
            },
            ['key', 'type', 'project', 'milestone'],
            array_reverse(
                array_reduce(
                    explode('/', $name),
                    fn($acc, $name) => [
                        ...$acc,
                        ...array_reverse(explode('#', $name))
                    ],
                    [],
                ),
            ),
        );
    }

    protected function getIssueKey(string $line): string
    {
        return self::getKey($line, '|');
    }

    protected function getMilestoneKey(string $line): string
    {
        return self::getKey($line, '^');
    }

    protected function getKey(string $line, string $separator): string
    {
        list($key) = self::getNameComponents(
            trim(explode(' ', trim(explode($separator, $line)[0]))[0])
        );
        return $key;
    }

    /**
     * @param string $line
     * @return string[]
     */
    protected function getIssueAttributes(string $line): array
    {
        return self::getAttributes($line, '|');
    }

    protected function getMilestoneAttributes(string $line): array
    {
        return self::getAttributes($line, '^');
    }

    protected function getAttributes(string $line, string $separator): array
    {
        return array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', trim(array_reverse(explode($separator, $line))[0]))
            ),
            fn(string $attribute) => !empty($attribute),
        );
    }

    protected function getLinksByAttributes(string $from, array $attributes, Mapper $mapper = null): array
    {
        return array_reduce(
            array_map(
                fn(string $linkAttribute) => explode(' ', $linkAttribute),
                array_filter(
                    $attributes,
                    fn(string $attribute) => in_array($attribute[0], ['&', '@']),
                ),
            ),
            fn(array $acc, array $linkAttributeData) => [
                ...$acc,
                [
                    'from' => $from,
                    'to' => $linkAttributeData[1],
                    'type' => !is_null($mapper)
                        ? ($linkAttributeData[0] === '&' ? current($mapper->sequenceLinkTypes) : current($mapper->scheduleLnkTypes))
                        : ($linkAttributeData[0] === '&' ? 'sequence' : 'schedule'),
                ],
            ],
            [],
        );
    }

    protected function getProjectBeginGap(): int
    {
        return array_reduce(
            self::getTracks(),
            fn($acc, $track) => min($acc, strlen($track) - strlen(ltrim($track))),
            PHP_INT_MAX
        );
    }

    protected function getProjectEndGap(): int
    {
        return array_reduce(
            self::getTracks(),
            fn($acc, $track) => min($acc, strlen($track) - strlen(rtrim($track))),
            PHP_INT_MAX
        );
    }

    protected function getProjectDate(): \DateTimeImmutable|null
    {
        return array_reduce(
            self::getMilestoneLines(),
            fn($acc, string $milestoneLine) => self::isEndMarkers()
                ? max($acc, self::getMilestoneDate($milestoneLine))
                : (
                is_null($acc)
                    ? self::getMilestoneDate($milestoneLine)
                    : min($acc, self::getMilestoneDate($milestoneLine))
                ),
        );
    }

    protected function getMilestoneDate(string $milestoneLine): \DateTimeImmutable|null
    {
        return new \DateTimeImmutable(
            explode(
                ' ',
                array_reduce(
                    array_filter(
                        self::getMilestoneAttributes($milestoneLine),
                        fn($attribute) => str_starts_with($attribute, '#')
                    ),
                    fn($acc, $attribute) => $attribute
                )
            )[1]
        );
    }

    protected function isEndMarkers(): bool
    {
        return
            array_reduce(
                self::getMilestoneLines(),
                fn($acc, $line) => max($acc, strrpos($line, '^')),
            ) >=
            array_reduce(
                array_map(
                    fn($line) => rtrim(substr($line, 0, strrpos($line, '|'))),
                    self::getIssueLines()
                ),
                fn($acc, $line) => max($acc, strlen($line)),
            );
    }

    /**
     * @return Line[]
     */
    protected function getIssueLines(): array
    {
        return array_values(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line instanceof IssueLine,
            ),
        );
    }

    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn(IssueLine $line) => $line->track,
            self::getIssueLines(),
        );
    }

    /**
     * @return Line[]
     */
    protected function getMilestoneLines(): array
    {
        return array_values(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line->type === LineType::Milestone,
            )
        );
    }

    protected function getContextLine(): Line|null
    {
        return array_reduce(
            array_filter(
                $this->getLines(),
                fn(Line $line) => $line->type === LineType::Context,
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
        return $this->lines = array_values(
            array_map(
                fn(string $content) => match (true) {
                    str_contains($content, '|') => new IssueLine($content),
                    str_contains($content, '^') => new Line($content),
                    str_contains($content, '>') => new Line($content),
                    default => new Line($content),
                },
                array_filter(
                    explode("\n", $this->description),
                    fn($line) => !empty(trim($line)),
                )
            )
        );
    }
}

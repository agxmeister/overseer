<?php

namespace Watch\Blueprint\Model\Builder\Subject;

use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Builder\Line\Subject\Issue as IssueLine;
use Watch\Blueprint\Model\Subject\Issue as IssueModel;
use Watch\Blueprint\Model\Track;
use Watch\Schedule\Mapper;

class Issue extends Builder
{
    private array $models = [];

    private int $endPosition = 0;

    private ?IssueModel $model;

    public function __construct(private readonly Mapper $mapper)
    {
    }

    public function reset(): self
    {
        $this->model = null;
        return $this;
    }

    public function release(): self
    {
        $this->models[] = $this->model;
        return $this->reset();
    }

    public function setModel(array $values, array $offsets, ...$defaults): self
    {
        $line = new IssueLine($values, $offsets, ...$defaults);
        list(
            'key' => $key,
            'type' => $type,
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->endPosition = $endMarkerOffset - $trackGap;
        $lineAttributes = $line->getAttributes($attributes);
        $lineLinks = $line->getLinks($key, $lineAttributes, $this->mapper);
        $this->model = new IssueModel(
            $key,
            $type,
            $project,
            $milestone,
            new Track($track),
            $lineLinks,
            $lineAttributes,
            $modifier === '~',
            $modifier === '+',
            str_contains($track, '*'),
        );
        return $this;
    }

    /**
     * @return IssueModel[]
     */
    public function flush(): array
    {
        $models = $this->models;
        $this->models = [];
        return $models;
    }

    public function getEndPosition(): int
    {
        return $this->endPosition;
    }
}

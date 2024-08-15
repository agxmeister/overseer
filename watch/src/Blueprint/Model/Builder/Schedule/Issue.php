<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Schedule\Issue as IssueModel;
use Watch\Blueprint\Model\Track;

class Issue extends Builder
{
    use HasLinks;

    private array $models = [];

    private int $endPosition = 0;

    private ?IssueModel $model;

    public function reset(): self
    {
        $this->model = null;
        return $this;
    }

    public function release(): self
    {
        $this->models[] = $this->model;
        $this->model = null;
        return $this;
    }

    public function setModel(Stroke $stroke): self
    {
        [
            'key' => $key,
            'type' => $type,
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
        ] = $stroke->parts;
        list('endMarker' => $endMarkerOffset) = $stroke->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->endPosition = $endMarkerOffset - $trackGap;
        $strokeAttributes = $this->getStrokeAttributes($attributes);
        $this->model = new IssueModel(
            $key,
            $type,
            $project,
            $milestone,
            new Track($track),
            $this->getLinks($key, $strokeAttributes),
            $strokeAttributes,
            $modifier === '~',
            $modifier === '+',
            str_contains($track, '*') || str_contains($track, 'x'),
            str_contains($track, 'x'),
            $modifier === '-',
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

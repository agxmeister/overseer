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
            'endMarker' => $endMarker,
            'attributes' => $attributes,
        ] = $stroke->dashes;
        $trackGap = strlen($track?->value) - strlen(rtrim($track?->value));
        $this->endPosition = $endMarker?->offset - $trackGap;
        $strokeAttributes = $this->getStrokeAttributes($attributes?->value ?? []);
        $this->model = new IssueModel(
            $key?->value,
            $type?->value,
            $project?->value,
            $milestone?->value,
            new Track($track?->value),
            $this->getLinks($key?->value, $strokeAttributes),
            $strokeAttributes,
            $modifier?->value === '~',
            $modifier?->value === '+',
            str_contains($track?->value, '*') || str_contains($track?->value, 'x'),
            str_contains($track?->value, 'x'),
            $modifier?->value === '-',
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

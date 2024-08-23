<?php

namespace Watch\Blueprint\Model\Builder\Subject;

use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Schedule\Milestone as MilestoneModel;

class Milestone extends Builder
{
    private array $models = [];

    private int $markerOffset = 0;

    private ?MilestoneModel $model;

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
            'marker' => $marker,
            'attributes' => $attributes,
        ] = $stroke->dashes;
        $this->markerOffset = $marker?->offset ?? 0;
        $this->model = new MilestoneModel($key?->value, $this->getStrokeAttributes($attributes?->value ?? []));
        return $this;
    }

    /**
     * @return MilestoneModel[]
     */
    public function flush(): array
    {
        $models = $this->models;
        $this->models = [];
        return $models;
    }

    public function getMarkerOffset(): int
    {
        return $this->markerOffset;
    }
}

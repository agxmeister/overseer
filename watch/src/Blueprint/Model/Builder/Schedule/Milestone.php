<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Builder\Stroke\Stroke;
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
        ['key' => $key] = $stroke->parts;
        ['marker' => $this->markerOffset] = $stroke->offsets;
        $this->model = new MilestoneModel($key, $stroke->attributes);
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

<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Builder\HasContext;
use Watch\Blueprint\Model\Builder\Line\Schedule\Milestone as MilestoneLine;
use Watch\Blueprint\Model\Schedule\Milestone as MilestoneModel;

class Milestone implements Builder
{
    use HasContext;

    private array $models = [];

    private ?MilestoneModel $model;

    public function reset(): Builder
    {
        $this->model = null;
        return $this;
    }

    public function release(): Builder
    {
        $this->models[] = $this->model;
        $this->model = null;
        return $this;
    }

    public function setModel(array $values, array $offsets, ...$defaults): Builder
    {
        $line = new MilestoneLine($values, $offsets, ...$defaults);
        list(
            'key' => $key,
            'attributes' => $attributes
            ) = $line->parts;
        list('marker' => $markerOffset) = $line->offsets;
        $this->context->setProjectMarkerOffset($markerOffset);
        $this->model = new MilestoneModel($key, $line->getAttributes($attributes));
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
}

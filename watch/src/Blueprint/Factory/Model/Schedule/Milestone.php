<?php

namespace Watch\Blueprint\Factory\Model\Schedule;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Factory\Line;
use Watch\Blueprint\Factory\Model\Builder;
use Watch\Blueprint\Model\Schedule\Milestone as MilestoneModel;

class Milestone implements Builder
{
    use HasAttributes, HasLinks;

    private array $models = [];

    private ?MilestoneModel $model;

    public function reset(): Builder
    {
        $this->model = null;
        return $this;
    }
    public function setModel(Line $line, Context $context): Builder
    {
        list(
            'key' => $key,
            'attributes' => $attributes
            ) = $line->parts;
        list('marker' => $markerOffset) = $line->offsets;
        $context->setProjectMarkerOffset($markerOffset);
        $this->model = new MilestoneModel($key, $this->getLineAttributes($attributes));
        return $this;
    }

    public function release(): Builder
    {
        $this->models[] = $this->model;
        return $this;
    }

    /**
     * @return MilestoneModel[]
     */
    public function get(): array
    {
        return $this->models;
    }
}

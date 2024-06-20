<?php

namespace Watch\Blueprint\Factory\Builder\Subject;

use Watch\Blueprint\Factory\Builder\Builder;
use Watch\Blueprint\Factory\Builder\HasAttributes;
use Watch\Blueprint\Factory\Builder\HasContext;
use Watch\Blueprint\Factory\Line\Subject\Milestone as MilestoneLine;
use Watch\Blueprint\Model\Schedule\Milestone as MilestoneModel;

class Milestone implements Builder
{
    use HasContext, HasAttributes, HasLinks;

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

    public function setModel(string $content, string $pattern, ...$defaults): Builder
    {
        $line = new MilestoneLine($content, $pattern, ...$defaults);
        list(
            'key' => $key,
            'attributes' => $attributes
            ) = $line->parts;
        list('marker' => $markerOffset) = $line->offsets;
        $this->context->setProjectMarkerOffset($markerOffset);
        $this->model = new MilestoneModel($key, $this->getLineAttributes($attributes));
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

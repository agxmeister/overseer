<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Schedule\Buffer as BufferModel;
use Watch\Blueprint\Model\Track;

class Buffer extends Builder
{
    use HasLinks;

    private array $models = [];

    private int $endPosition = 0;

    private ?BufferModel $model;

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
            'track' => $track,
            'endMarker' => $endMarker,
            'attributes' => $attributes,
        ] = $stroke->dashes;
        $trackGap = strlen($track?->value) - strlen(rtrim($track?->value));
        $this->endPosition = $endMarker?->offset - $trackGap;
        $consumption = substr_count(trim($track?->value), '!');
        $strokeAttributes = $this->getStrokeAttributes($attributes?->value ?? []);
        $this->model = new BufferModel(
            $key?->value,
            $type?->value,
            new Track($track?->value),
            $this->getLinks($key?->value, $strokeAttributes),
            $strokeAttributes,
            $consumption,
        );
        return $this;
    }

    /**
     * @return BufferModel[]
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

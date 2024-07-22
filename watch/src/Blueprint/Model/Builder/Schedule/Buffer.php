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
        list(
            'key' => $key,
            'type' => $type,
            'track' => $track,
            ) = $stroke->parts;
        list('endMarker' => $endMarkerOffset) = $stroke->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->endPosition = $endMarkerOffset - $trackGap;
        $consumption = substr_count(trim($track), '!');
        $strokeAttributes = $stroke->attributes;
        $this->model = new BufferModel(
            $key,
            $type,
            new Track($track),
            $this->getLinks($key, $strokeAttributes),
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

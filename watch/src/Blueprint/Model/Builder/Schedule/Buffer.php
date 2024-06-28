<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Builder\Line\Schedule\Buffer as BufferLine;
use Watch\Blueprint\Model\Schedule\Buffer as BufferModel;
use Watch\Blueprint\Model\Track;

class Buffer extends Builder
{
    private array $models = [];

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

    public function setModel(array $values, array $offsets, ...$defaults): self
    {
        $line = new BufferLine($values, $offsets, ...$defaults);
        list(
            'key' => $key,
            'type' => $type,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->context->setIssuesEndPosition($endMarkerOffset - $trackGap);
        $lineAttributes = $line->getAttributes($attributes);
        $lineLinks = $line->getLinks($key, $lineAttributes);
        $consumption = substr_count(trim($track), '!');
        $this->model = new BufferModel(
            $key,
            $type,
            new Track($track),
            $lineLinks,
            $lineAttributes,
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
}

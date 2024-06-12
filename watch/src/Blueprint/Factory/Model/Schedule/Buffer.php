<?php

namespace Watch\Blueprint\Factory\Model\Schedule;

use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Factory\Line;
use Watch\Blueprint\Factory\Model\Builder;
use Watch\Blueprint\Model\Schedule\Buffer as BufferModel;
use Watch\Blueprint\Model\Track;

class Buffer implements Builder
{
    use HasAttributes, HasLinks;

    private array $models = [];

    private ?BufferModel $model;

    public function reset(): Builder
    {
        $this->model = null;
        return $this;
    }
    public function setModel(Line $line, Context $context): Builder
    {
        list(
            'key' => $key,
            'type' => $type,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $context->setIssuesEndPosition($endMarkerOffset - $trackGap);
        $lineAttributes = $this->getLineAttributes($attributes);
        $lineLinks = $this->getLineLinks($key, $lineAttributes);
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

    public function release(): Builder
    {
        $this->models[] = $this->model;
        return $this;
    }

    /**
     * @return BufferModel[]
     */
    public function get(): array
    {
        return $this->models;
    }
}

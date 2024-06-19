<?php

namespace Watch\Blueprint\Factory\Builder\Schedule;

use Watch\Blueprint\Factory\Builder\HasContext;
use Watch\Blueprint\Factory\Line;
use Watch\Blueprint\Factory\Builder\Builder;
use Watch\Blueprint\Factory\Builder\HasAttributes;
use Watch\Blueprint\Model\Schedule\Buffer as BufferModel;
use Watch\Blueprint\Model\Track;

class Buffer implements Builder
{
    use HasContext, HasAttributes, HasLinks;

    private array $models = [];

    private ?BufferModel $model;

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
        $line = new Line($content, $pattern, ...$defaults);
        list(
            'key' => $key,
            'type' => $type,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->context->setIssuesEndPosition($endMarkerOffset - $trackGap);
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

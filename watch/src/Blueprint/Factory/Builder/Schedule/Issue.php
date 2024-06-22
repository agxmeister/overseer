<?php

namespace Watch\Blueprint\Factory\Builder\Schedule;

use Watch\Blueprint\Factory\Builder\Builder;
use Watch\Blueprint\Factory\Builder\HasContext;
use Watch\Blueprint\Factory\Line\Schedule\Issue as IssueLine;
use Watch\Blueprint\Model\Schedule\Issue as IssueModel;
use Watch\Blueprint\Model\Track;

class Issue implements Builder
{
    use HasContext;

    private array $models = [];

    private ?IssueModel $model;

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
        $line = new IssueLine($content, $pattern, ...$defaults);
        list(
            'key' => $key,
            'type' => $type,
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
            ) = $line->parts;
        list('endMarker' => $endMarkerOffset) = $line->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->context->setIssuesEndPosition($endMarkerOffset - $trackGap);
        $lineAttributes = $line->getAttributes($attributes);
        $lineLinks = $line->getLinks($key, $lineAttributes);
        $this->model = new IssueModel(
            $key,
            $type,
            $project,
            $milestone,
            new Track($track),
            $lineLinks,
            $lineAttributes,
            $modifier === '~',
            $modifier === '+',
            str_contains($track, '*') || str_contains($track, 'x'),
            str_contains($track, 'x'),
            $modifier === '-',
        );
        return $this;
    }

    /**
     * @return IssueModel[]
     */
    public function flush(): array
    {
        $models = $this->models;
        $this->models = [];
        return $models;
    }
}

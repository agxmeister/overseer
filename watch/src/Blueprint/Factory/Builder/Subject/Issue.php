<?php

namespace Watch\Blueprint\Factory\Builder\Subject;

use Watch\Blueprint\Factory\Builder\Builder;
use Watch\Blueprint\Factory\Builder\HasAttributes;
use Watch\Blueprint\Factory\Builder\HasContext;
use Watch\Blueprint\Factory\Line\Subject\Issue as IssueLine;
use Watch\Blueprint\Model\Subject\Issue as IssueModel;
use Watch\Blueprint\Model\Track;
use Watch\Schedule\Mapper;

class Issue implements Builder
{
    use HasContext, HasAttributes, HasLinks;

    private array $models = [];

    private ?IssueModel $model;

    public function __construct(private readonly Mapper $mapper)
    {
    }

    public function reset(): Builder
    {
        $this->context = null;
        $this->model = null;
        return $this;
    }

    public function release(): Builder
    {
        $this->models[] = $this->model;
        return $this->reset();
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
        $lineAttributes = $this->getLineAttributes($attributes);
        $lineLinks = $this->getLineLinks($key, $lineAttributes);
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
            str_contains($track, '*'),
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

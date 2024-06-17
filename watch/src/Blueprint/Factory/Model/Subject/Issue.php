<?php

namespace Watch\Blueprint\Factory\Model\Subject;

use Watch\Blueprint\Factory\Context;
use Watch\Blueprint\Factory\Line;
use Watch\Blueprint\Factory\Model\Builder;
use Watch\Blueprint\Factory\Model\HasAttributes;
use Watch\Blueprint\Model\Subject\Issue as IssueModel;
use Watch\Blueprint\Model\Track;
use Watch\Schedule\Mapper;

class Issue implements Builder
{
    use HasAttributes, HasLinks;

    private array $models = [];

    private ?IssueModel $model;

    public function __construct(private readonly Mapper $mapper)
    {
    }

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

    public function setModel(Line $line, Context $context): Builder
    {
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
        $context->setIssuesEndPosition($endMarkerOffset - $trackGap);
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

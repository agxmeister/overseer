<?php

namespace Watch\Blueprint\Model\Builder\Schedule;

use Watch\Blueprint\Builder\Stroke\Stroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Schedule\Issue as IssueModel;
use Watch\Blueprint\Model\Track;

class Issue extends Builder
{
    private array $models = [];

    private int $endPosition = 0;

    private ?IssueModel $model;

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
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            ) = $stroke->parts;
        list('endMarker' => $endMarkerOffset) = $stroke->offsets;
        $trackGap = strlen($track) - strlen(rtrim($track));
        $this->endPosition = $endMarkerOffset - $trackGap;
        $strokeAttributes = $stroke->getAttributes($stroke->attributes);
        $this->model = new IssueModel(
            $key,
            $type,
            $project,
            $milestone,
            new Track($track),
            $this->getLinks($key, $strokeAttributes),
            $strokeAttributes,
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

    public function getEndPosition(): int
    {
        return $this->endPosition;
    }

    private function getLinks(string $key, array $attributes): array
    {
        return array_reduce(
            array_filter(
                $attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $key,
                    'to' => $attribute->value,
                    'type' => $attribute->type === AttributeType::Sequence ? 'sequence' : 'schedule',
                ],
            ],
            [],
        );
    }
}

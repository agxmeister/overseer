<?php

namespace Watch\Blueprint\Model\Builder\Subject;

use Watch\Blueprint\Builder\Asset\Stroke;
use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;
use Watch\Blueprint\Model\Builder\Builder;
use Watch\Blueprint\Model\Subject\Issue as IssueModel;
use Watch\Blueprint\Model\Track;
use Watch\Schedule\Mapper;

class Issue extends Builder
{
    private array $models = [];

    private int $endPosition = 0;

    private ?IssueModel $model;

    public function __construct(private readonly Mapper $mapper)
    {
    }

    public function reset(): self
    {
        $this->model = null;
        return $this;
    }

    public function release(): self
    {
        $this->models[] = $this->model;
        return $this->reset();
    }

    public function setModel(Stroke $stroke): self
    {
        [
            'key' => $key,
            'type' => $type,
            'project' => $project,
            'milestone' => $milestone,
            'modifier' => $modifier,
            'track' => $track,
            'endMarker' => $endMarker,
            'attributes' => $attributes,
        ] = $stroke->dashes;
        $trackGap = strlen($track?->value) - strlen(rtrim($track?->value));
        $this->endPosition = $endMarker?->offset - $trackGap;
        $strokeAttributes = $this->getStrokeAttributes($attributes?->value ?? []);
        $this->model = new IssueModel(
            $key?->value,
            $type?->value,
            $project?->value,
            $milestone?->value,
            new Track($track?->value),
            $this->getLinks($key?->value, $strokeAttributes, $this->mapper),
            $strokeAttributes,
            $modifier?->value === '~',
            $modifier?->value === '+',
            str_contains($track?->value, '*'),
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

    private function getLinks(string $key, array $attributes, Mapper $mapper): array
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
                    'type' => $attribute->type === AttributeType::Sequence
                        ? current($mapper->sequenceLinkTypes)
                        : current($mapper->scheduleLnkTypes),
                ],
            ],
            [],
        );
    }
}

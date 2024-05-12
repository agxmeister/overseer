<?php

namespace Watch\Blueprint\Line;

readonly abstract class TrackLine extends Line
{
    public Track $track;

    public function __construct(
        public string $key,
        public string $type,
        string $track,
        array $attributes,
        public int $endMarkerOffset,
    )
    {
        parent::__construct($attributes);
        $this->setTrack($track);
    }

    public function getEndPosition(): int
    {
        return $this->endMarkerOffset - $this->track->gap;
    }

    public function getLinks(): array
    {
        return array_reduce(
            array_filter(
                $this->attributes,
                fn(Attribute $attribute) => in_array($attribute->type, [AttributeType::Schedule, AttributeType::Sequence]),
            ),
            fn(array $acc, Attribute $attribute) => [
                ...$acc,
                [
                    'from' => $this->key,
                    'to' => $attribute->value,
                    'type' => $attribute->type === AttributeType::Sequence ? 'sequence' : 'schedule',
                ],
            ],
            [],
        );
    }

    protected function setTrack(string $trackContent): void
    {
        $this->track = new Track($trackContent);
    }
}

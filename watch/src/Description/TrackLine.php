<?php

namespace Watch\Description;

readonly abstract class TrackLine extends Line
{
    public string $key;
    public string $type;

    public Track $track;

    /** @var Attribute[]  */
    public array $attributes;

    public function __construct($content)
    {
        parent::__construct($content);

        list($meta, $track, $attributes) = $this->getValues($this->content, '|', false, meta: '', track: '', attributes: '');
        list($name) = $this->getValues($meta, ' ', false, name: '');
        list($this->key, $this->type) = $this->getValues($name, '/', true, key: '', type: '');

        $this->track = new Track($track);

        $this->attributes = array_map(
            fn(string $content) => new Attribute($content),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $attributes)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    public function getEndPosition(): int
    {
        return strrpos($this->content, '|') - $this->track->gap;
    }
}

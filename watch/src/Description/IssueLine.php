<?php

namespace Watch\Description;

readonly class IssueLine extends Line
{
    public string $name;
    public string $key;
    public string $type;
    public string $project;
    public string $milestone;
    public bool $scheduled;
    public bool $started;
    public bool $completed;
    public bool $ignored;

    public Track $track;

    public array $attributes;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $track, $attributes) = $this->getValues($this->content, '|', ['', '', '']);
        list($this->name, $modifier) = $this->getValues($meta, ' ', ['', '']);
        list($this->key, $this->type, $delivery) = $this->getValues($this->name, '/', ['', 'T', ''], true);
        list($this->project, $this->milestone) = $this->getValues($delivery, '#', ['PRJ', '']);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($track, '*');
        $this->track = new Track($track);
        $this->attributes = array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', $attributes)
            ),
            fn(string $attribute) => !empty($attribute),
        );
    }

    public function getEndPosition(): int
    {
        return strrpos($this->content, '|') - $this->track->gap;
    }
}

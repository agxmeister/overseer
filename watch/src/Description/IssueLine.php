<?php

namespace Watch\Description;

readonly class IssueLine extends TrackLine
{
    public string $project;
    public string $milestone;
    public bool $scheduled;
    public bool $started;
    public bool $completed;
    public bool $ignored;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $track) = $this->getValues($this->content, '|', ['', '']);
        list($name, $modifier) = $this->getValues($meta, ' ', ['', '']);
        list($key, $type, $delivery) = $this->getValues($name, '/', ['', 'T', ''], true);
        list($this->project, $this->milestone) = $this->getValues($delivery, '#', ['PRJ', '']);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($track, '*');
    }
}

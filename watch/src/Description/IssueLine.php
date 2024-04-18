<?php

namespace Watch\Description;

readonly class IssueLine extends TrackLine
{
    public string $project;
    public string $milestone;
    public bool $scheduled;
    public bool $critical;
    public bool $started;
    public bool $completed;
    public bool $ignored;

    public function __construct($content)
    {
        parent::__construct($content);
        ['name' => $name, 'modifier' => $modifier, 'track' => $track] = $this->getValuesByPattern(
            $this->content,
            '/\s*(?<name>\s*[\w\d\-\/#]+)\s+(?<modifier>[~+\-]?)\|(?<track>[x*. ]*)\|\s*(?<attributes>.*)/',
        );
        list($key, $type, $delivery) = $this->getValues($name, '/', true, key: '', type: 'T', delivery: '');
        list($this->project, $this->milestone) = $this->getValues($delivery, '#', false, project: 'PRJ', milestone: '');
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($track, '*') || str_contains($track, 'x');
        $this->critical = str_contains($track, 'x');
    }
}

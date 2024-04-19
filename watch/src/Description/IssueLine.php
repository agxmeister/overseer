<?php

namespace Watch\Description;

readonly class IssueLine extends TrackLine
{
    public string $project;
    public string|null $milestone;
    public bool $scheduled;
    public bool $critical;
    public bool $started;
    public bool $completed;
    public bool $ignored;

    public function __construct($content)
    {
        parent::__construct($content);
        [
            'project' => $project,
            'milestone' => $milestone,
            'type' => $type,
            'key' => $key,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
        ] = $this->getValuesByPattern(
            $this->content,
            '/\s*(((((?<project>[\w\d\-]+)(#(?<milestone>[\w\d\-]+))?)\/)?(?<type>[\w\d]+)\/)?(?<key>[\w\d\-]+))\s+(?<modifier>[~+\-]?)\|(?<track>[x*. ]*)\|\s*(?<attributes>.*)/',
        );
        $this->project = $project ?? 'PRJ';
        $this->milestone = $milestone;
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($track, '*') || str_contains($track, 'x');
        $this->critical = str_contains($track, 'x');
    }
}

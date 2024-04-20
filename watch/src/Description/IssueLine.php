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
            'project' => $this->project,
            'milestone' => $this->milestone,
            'type' => $type,
            'key' => $key,
            'modifier' => $modifier,
            'track' => $track,
            'attributes' => $attributes,
        ] = $this->getValuesByPattern(
            $this->content,
            '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)\|(?<track>[x*.\s]*)\|\s*(?<attributes>.*)/',
            project: 'PRJ',
            type: 'T',
        );
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($track, '*') || str_contains($track, 'x');
        $this->critical = str_contains($track, 'x');
    }
}

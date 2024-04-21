<?php

namespace Watch\Description;

readonly class IssueLine extends TrackLine
{
    public string $key;
    public string $type;
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
            'key' => $this->key,
            'type' => $this->type,
            'project' => $this->project,
            'milestone' => $this->milestone,
            'modifier' => $modifier,
            'track' => $trackContent,
            'attributes' => $attributesContent,
        ] = $this->getValuesByPattern(
            $this->content,
            '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+\-]?)\|(?<track>[x*.\s]*)\|\s*(?<attributes>.*)/',
            project: 'PRJ',
            type: 'T',
        );
        $this->setTrack($trackContent);
        $this->setAttributes($attributesContent);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->ignored = $modifier === '-';
        $this->scheduled = str_contains($trackContent, '*') || str_contains($trackContent, 'x');
        $this->critical = str_contains($trackContent, 'x');
    }
}

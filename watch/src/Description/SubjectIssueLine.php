<?php

namespace Watch\Description;

readonly class SubjectIssueLine extends TrackLine
{
    public string $key;
    public string $type;
    public string $project;
    public string|null $milestone;
    public bool $scheduled;
    public bool $started;
    public bool $completed;

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
        ] = Utils::getStringParts(
            $this->content,
            '/\s*(((((?<project>[\w\-]+)(#(?<milestone>[\w\-]+))?)\/)?(?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+(?<modifier>[~+]?)\|(?<track>[*.\s]*)\|\s*(?<attributes>.*)/',
            project: 'PRJ',
            type: 'T',
        );
        $this->setTrack($trackContent);
        $this->setAttributes($attributesContent);
        $this->started = $modifier === '~';
        $this->completed = $modifier === '+';
        $this->scheduled = str_contains($trackContent, '*');
    }
}

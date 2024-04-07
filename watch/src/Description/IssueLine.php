<?php

namespace Watch\Description;

readonly class IssueLine extends Line
{
    public string $name;
    public string $key;

    public Track $track;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $track) = $this->getValues($this->content, '|', ['', '']);
        list($this->name) = $this->getValues($meta, ' ', ['']);
        list($this->key) = $this->getValues($this->name, '/', [''], true);
        $this->track = new Track($track);
    }

    protected function getAttributesContent(): string
    {
        return trim(array_reverse(explode('|', $this->content))[0]);
    }
}

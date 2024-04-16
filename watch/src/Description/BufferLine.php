<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    public int $consumption;
    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $track) = $this->getValues($this->content, '|', false, meta: '', track: '');
        $this->consumption = substr_count(trim($track), '!');
    }
}

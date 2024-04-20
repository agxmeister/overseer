<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    public int $consumption;
    public function __construct($content)
    {
        parent::__construct($content);
        [
            'type' => $type,
            'key' => $key,
            'track' => $track,
            'attributes' => $attributes,
        ] = $this->getValuesByPattern(
            $this->content,
            '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+\|(?<track>[_!\s]*)\|\s*(?<attributes>.*)/',
        );
        $this->consumption = substr_count(trim($track), '!');
    }
}

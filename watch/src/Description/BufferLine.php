<?php

namespace Watch\Description;

readonly class BufferLine extends TrackLine
{
    const string PATTERN = '/\s*(((?<type>[\w\-]+)\/)?(?<key>[\w\-]+))\s+\|(?<track>[_!\s]*)\|\s*(?<attributes>.*)/';

    public string $key;
    public string $type;
    public int $consumption;
    public function __construct($content)
    {
        parent::__construct($content);
        [
            'key' => $this->key,
            'type' => $this->type,
            'track' => $trackContent,
            'attributes' => $attributesContent,
        ] = Utils::getStringParts(
            $this->content,
            self::PATTERN,
            type: 'T',
        );
        $this->setTrack($trackContent);
        $this->setAttributes($attributesContent);
        $this->consumption = substr_count(trim($trackContent), '!');
    }
}

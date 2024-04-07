<?php

namespace Watch\Description;

readonly class IssueLine extends Line
{
    public string $name;
    public Track $track;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $track) = $this->getValues($this->content, '|', ['meta', 'track']);
        list($name) = $this->getValues($meta, ' ', ['name']);;
        $this->name = trim($name);
        $this->track = new Track($track);
    }

    protected function getAttributesContent(): string
    {
        return trim(array_reverse(explode('|', $this->content))[0]);
    }

    protected function getValues($string, $separator, $keys): array
    {
        return array_map(
            fn($key, $value) => $value,
            $keys,
            array_filter(
                explode($separator, $string),
                fn(string $part) => !empty($part),
            )
        );
    }
}

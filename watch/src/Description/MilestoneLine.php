<?php

namespace Watch\Description;

use DateTimeImmutable;

readonly class MilestoneLine extends Line
{
    public string $name;
    public string $key;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta) = $this->getValues($this->content, '^', ['']);
        list($this->name) = $this->getValues($meta, ' ', ['']);
        list($this->key) = $this->getValues($this->name, '/', [''], true);
    }

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            explode(
                ' ',
                array_reduce(
                    array_filter(
                        $this->getAttributes(),
                        fn($attribute) => str_starts_with($attribute, '#')
                    ),
                    fn($acc, $attribute) => $attribute
                )
            )[1]
        );
    }

    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '^');
    }

    protected function getAttributesContent(): string
    {
        return trim(array_reverse(explode('^', $this->content))[0]);
    }
}

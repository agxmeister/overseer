<?php

namespace Watch\Description;

use DateTimeImmutable;

readonly class MilestoneLine extends Line
{
    public string $name;
    public string $key;

    public array $attributes;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $attributes) = $this->getValues($this->content, '^', ['', '']);
        list($this->name) = $this->getValues($meta, ' ', ['']);
        list($this->key) = $this->getValues($this->name, '/', [''], true);
        $this->attributes = array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', $attributes)
            ),
            fn(string $attribute) => !empty($attribute),
        );
    }

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            explode(
                ' ',
                array_reduce(
                    array_filter(
                        $this->attributes,
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
}

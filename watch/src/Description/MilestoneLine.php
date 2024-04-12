<?php

namespace Watch\Description;

use DateTimeImmutable;
use function DI\value;

readonly class MilestoneLine extends Line
{
    public string $name;
    public string $key;

    /** @var Attribute[] */
    public array $attributes;

    public function __construct($content)
    {
        parent::__construct($content);
        list($meta, $attributes) = $this->getValues($this->content, '^', ['', '']);
        list($this->name) = $this->getValues($meta, ' ', ['']);
        list($this->key) = $this->getValues($this->name, '/', [''], true);
        $this->attributes = array_map(
            fn(string $content) => new Attribute($content),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $attributes)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    public function getDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(
            array_reduce(
                array_filter(
                    $this->attributes,
                    fn(Attribute $attribute) => $attribute->type === AttributeType::Date
                ),
                fn(Attribute|null $acc, Attribute $attribute) => $attribute,
            )?->value,
        );
    }

    public function getMarkerPosition(): int
    {
        return strrpos($this->content, '^');
    }
}

<?php

namespace Watch\Description;

use DateTimeImmutable;

readonly class MilestoneLine extends Line
{
    public string $key;

    /** @var Attribute[] */
    public array $attributes;

    public function __construct($content)
    {
        parent::__construct($content);
        [
            'key' => $this->key,
            'attributes' => $attributesContent,
        ] = $this->getValuesByPattern(
            $this->content,
            '/\s*(?<key>[\w\-]+)?\s+\^\s+(?<attributes>.*)/',
            key: 'PRJ',
        );
        $this->setAttributes($attributesContent);
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

<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Blueprint as BlueprintModel;
use Watch\Blueprint\Factory\Context\Context;
use Watch\Blueprint\Line\Attribute;
use Watch\Blueprint\Line\AttributeType;
use Watch\Blueprint\Line\Line;
use Watch\Blueprint\Line\Track;

abstract readonly class Blueprint
{
    abstract public function create(string $content): BlueprintModel;

    /**
     * @param string $content
     * @return Line[]
     */
    protected function getLines(string $content, Context &$context): array
    {
        $contents = array_filter(
            explode("\n", $content),
            fn($line) => !empty(trim($line)),
        );
        return array_values(
            array_filter(
                array_map(
                    fn(string $content) => $this->getLine($content, $context),
                    $contents,
                ),
                fn(Line|null $line) => !is_null($line),
            )
        );
    }

    protected function getLineAttributes(string $content): array
    {
        return array_map(
            fn(string $attribute) => $this->getLineAttribute($attribute),
            array_values(
                array_filter(
                    array_map(
                        fn($attribute) => trim($attribute),
                        explode(',', $content)
                    ),
                    fn(string $attribute) => !empty($attribute),
                )
            )
        );
    }

    protected function getLineAttribute(string $content): Attribute
    {
        list($code, $value) = explode(' ', $content);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }

    protected function getTrack(string $content): Track
    {
        return new Track($content);
    }

    abstract protected function getLine(string $content, Context &$context): ?Line;
}

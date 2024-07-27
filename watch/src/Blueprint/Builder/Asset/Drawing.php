<?php

namespace Watch\Blueprint\Builder\Asset;

use Watch\Blueprint\Model\Attribute;
use Watch\Blueprint\Model\AttributeType;

readonly class Drawing
{
    public array $strokes;

    public function __construct(string $drawing)
    {
        $this->strokes = array_filter(
            explode("\n", $drawing),
            fn($line) => !empty(trim($line)),
        );
    }

    public function getStrokes(Parser $parser, ...$defaults): ?array
    {
        return array_map(
            fn(array $match) => $this->createStroke($match, ...$defaults),
            $parser->getMatches($this->strokes),
        );
    }

    public function getStroke(Parser $parser, ...$defaults): ?Stroke
    {
        $match = current($parser->getMatches($this->strokes));
        if ($match === false) {
            return null;
        }
        return $this->createStroke($match, ...$defaults);
    }

    private function getStrokeAttributes(string $attributes): array
    {
        return array_map(
            fn(string $attribute) => $this->getStrokeAttribute($attribute),
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

    private function getStrokeAttribute(string $attribute): Attribute
    {
        [$code, $value] = explode(' ', $attribute);
        $type = match ($code) {
            '@' => AttributeType::Schedule,
            '&' => AttributeType::Sequence,
            '#' => AttributeType::Date,
            default => AttributeType::Default,
        };
        return new Attribute($type, $value);
    }

    private function createStroke($match, ...$defaults): Stroke
    {
        [$parts, $offsets] = $match;
        return new Stroke(
            array_filter(
                $parts,
                fn(string $key) => $key !== 'attributes',
                ARRAY_FILTER_USE_KEY
            ),
            array_filter(
                $offsets,
                fn(string $key) => $key !== 'attributes',
                ARRAY_FILTER_USE_KEY
            ),
            $this->getStrokeAttributes($parts['attributes'] ?? ''),
            ...$defaults
        );
    }
}

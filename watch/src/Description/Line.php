<?php

namespace Watch\Description;

abstract readonly class Line
{
    public function __construct(public string $content)
    {
    }

    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * @return string[]
     */
    public function getAttributes(): array
    {
        return array_filter(
            array_map(
                fn($attribute) => trim($attribute),
                explode(',', $this->getAttributesContent())
            ),
            fn(string $attribute) => !empty($attribute),
        );
    }

    protected function getValues($string, $separator, $defaults, $reverse = false): array
    {
        return array_map(
            fn($default, $value) => $value ?? $default,
            $defaults,
            array_filter(
                $reverse
                    ? array_reverse(explode($separator, $string))
                    : explode($separator, $string),
                fn(string $part) => !empty($part),
            )
        );
    }

    abstract protected function getAttributesContent();
}

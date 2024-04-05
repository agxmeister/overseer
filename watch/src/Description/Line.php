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

    abstract protected function getAttributesContent();
}

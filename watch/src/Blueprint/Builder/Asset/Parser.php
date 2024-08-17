<?php

namespace Watch\Blueprint\Builder\Asset;

readonly class Parser
{
    private array $defaults;

    public function __construct(private string $pattern, ...$defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * @param string[] $lines
     * @return array
     */
    public function getMatches(array $lines): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn(string $line) => $this->getMatch($line),
                    $lines,
                ),
                fn($match) => !is_null($match),
            )
        );
    }

    public function getMatch(string $line): ?array
    {
        if (!preg_match($this->pattern, $line, $matches, PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL)) {
            return null;
        }
        $namedMatches = array_filter(
            array_merge(
                array_map(
                    fn($default) => [$default, null],
                    $this->defaults,
                ),
                array_filter(
                    $matches,
                    fn($match) => !is_null($match[0]),
                ),
            ),
            fn($key) => is_string($key),
            ARRAY_FILTER_USE_KEY,
        );
        $plainNamedMatches = array_map(
            fn($key, $match) => [
                $this->getKey($key),
                $this->getValue($match[0], $this->getType($key)),
                $match[1],
            ],
            array_keys($namedMatches),
            array_values($namedMatches),
        );
        return [
            array_reduce(
                $plainNamedMatches,
                fn($acc, $value) => [...$acc, $value[0] => $value[1]],
                [],
            ),
            array_reduce(
                $plainNamedMatches,
                fn($acc, $value) => [...$acc, $value[0] => $value[2]],
                [],
            )
        ];
    }

    private function getKey(string $key): string
    {
        [$key] = explode('_', $key, 2);
        return $key;
    }

    private function getType(string $key): ?string
    {
        [, $type] = array_merge(explode('_', $key, 2), [null]);
        return $type;
    }

    private function getValue($value, $type): mixed
    {
        return match ($type) {
            'csv' => $this->getCsvValue($value),
            default => $value,
        };
    }

    private function getCsvValue($value): array
    {
        if (empty(trim($value))) {
            return [];
        }
        return array_map(
            fn(string $part) => trim($part),
            explode(',', $value),
        );
    }
}

<?php

namespace Watch\Schedule\Model;

use Watch\Utils;

abstract class Node
{
    /**
     * @var Link[]
     */
    private array $followers = [];

    /**
     * @var Link[]
     */
    private array $preceders = [];

    public function __construct(public readonly string $name, private readonly int $length = 0, private array $attributes = [])
    {
    }

    public function __clone()
    {
        $this->followers = [];
        $this->preceders = [];
    }

    public function follow(Node $node, string $type = Link::TYPE_SEQUENCE): void
    {
        if ($this->hasLink($this->preceders, $node)) {
            return;
        }
        $this->preceders[] = new Link($node, $type);
        $node->precede($this, $type);
    }

    public function unfollow(Node $node, string|null $type = null): void
    {
        $link = $this->getLink($this->preceders, $node, $type);
        if (is_null($link)) {
            return;
        }
        $this->preceders = array_filter($this->preceders, fn(Link $l) => $l !== $link);
        $node->unprecede($this, $type);
    }

    public function precede(Node $node, string $type = Link::TYPE_SEQUENCE): void
    {
        if ($this->hasLink($this->followers, $node)) {
            return;
        }
        $this->followers[] = new Link($node, $type);
        $node->follow($this, $type);
    }

    public function unprecede(Node $node, string|null $type = null): void
    {
        $link = $this->getLink($this->followers, $node, $type);
        if (is_null($link)) {
            return;
        }
        $this->followers = array_filter($this->followers, fn(Link $l) => $l !== $link);
        $node->unfollow($this, $type);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasPreceders(): bool
    {
        return !empty($this->preceders);
    }

    /**
     * @param string[] $types
     * @return Node[]
     */
    public function getFollowers(array $types = []): array
    {
        return array_map(fn(Link $link) => $link->node, array_filter(
            $this->followers,
            fn(Link $link) => empty($types) || in_array($link->type, $types)
        ));
    }

    /**
     * @param string[] $types
     * @return Link[]
     */
    public function getFollowLinks(array $types = []): array
    {
        return array_filter(
            $this->followers,
            fn(Link $link) => empty($types) || in_array($link->type, $types)
        );
    }

    /**
     * @param bool $isRecursively
     * @param string[] $types
     * @return Node[]
     */
    public function getPreceders(bool $isRecursively = false, array $types = []): array
    {
        $links = array_filter(
            $this->preceders,
            fn(Link $link) => empty($types) || in_array($link->type, $types)
        );
        $preceders = array_map(fn(Link $link) => $link->node, $links);
        if (!$isRecursively) {
            return [...$preceders];
        }
        foreach ($links as $link) {
            $preceders = [...$preceders, ...$link->node->getPreceders(true)];
        }
        return Utils::getUnique($preceders, fn(Node $node) => $node->getName());
    }

    /**
     * @param string[] $types
     * @return Link[]
     */
    public function getPrecedeLinks(array $types = []): array
    {
        return array_filter(
            $this->preceders,
            fn(Link $link) => empty($types) || in_array($link->type, $types)
        );
    }

    public function getDistance(bool $withPreceders = false, array $types = []): int
    {
        $followers = $this->getFollowers();
        if (empty($followers)) {
            return $this->getLength($withPreceders, $types);
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $followers)) + $this->getLength($withPreceders, $types);
    }

    public function getLength(bool $withPreceders = false, array $types = []): int
    {
        $effectiveLength = $this->getAttribute('ignored', false) ? 0 : $this->length;
        if (!$withPreceders) {
            return $effectiveLength;
        }
        $preceders = $this->getPreceders(true, $types);
        if (empty($preceders)) {
            return $effectiveLength;
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $preceders)) - $this->getDistance() + $effectiveLength;
    }

    public function getCompletion(): int
    {
        return $this->getDistance() - $this->getLength();
    }

    public function getSchedule(): array|string
    {
        return array_map(fn(Node $node) => [$node->getName(), $node->getLength(), $node->getDistance()], $this->getPreceders(true));
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    private function getLink(array $links, Node $node, string|null $type = null): Link|null
    {
        return array_reduce($links, fn(Link|null $acc, Link $link) => (
            $link->node === $node && (is_null($type) || $link->type === $type)
        ) ? $link : $acc);
    }

    private function hasLink(array $links, Node $node, string|null $type = null): bool
    {
        return !is_null($this->getLink($links, $node, $type));
    }
}

<?php

namespace Watch\Schedule\Model;

use Watch\Schedule\Utils;

class Node
{
    /**
     * @var Link[]
     */
    private array $followers = [];

    /**
     * @var Link[]
     */
    private array $preceders = [];

    public function __construct(protected string $name, private int $length)
    {
    }

    public function follow(Node $node, string $type = Link::TYPE_SEQUENCE): void
    {
        if ($this->hasLink($this->preceders, $node)) {
            return;
        }
        $this->preceders[] = new Link($node, $type);
        $node->precede($this, $type);
    }

    public function unfollow(Node $node): void
    {
        $link = $this->getLink($this->preceders, $node);
        if (is_null($link)) {
            return;
        }
        $this->preceders = array_filter($this->preceders, fn(Link $l) => $l !== $link);
        $node->unprecede($this);
    }

    public function precede(Node $node, string $type = Link::TYPE_SEQUENCE): void
    {
        if ($this->hasLink($this->followers, $node)) {
            return;
        }
        $this->followers[] = new Link($node, $type);
        $node->follow($this, $type);
    }

    public function unprecede(Node $node): void
    {
        $link = $this->getLink($this->followers, $node);
        if (is_null($link)) {
            return;
        }
        $this->followers = array_filter($this->followers, fn(Link $l) => $l !== $link);
        $node->unfollow($this);
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
        return array_map(fn(Link $link) => $link->getNode(), array_filter(
            $this->followers,
            fn(Link $link) => empty($types) || in_array($link->getType(), $types)
        ));
    }

    public function getFollowLinks(array $types = []): array
    {
        return array_filter(
            $this->followers,
            fn(Link $link) => empty($types) || in_array($link->getType(), $types)
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
            fn(Link $link) => empty($types) || in_array($link->getType(), $types)
        );
        $preceders = array_map(fn(Link $link) => $link->getNode(), $links);
        if (!$isRecursively) {
            return [...$preceders];
        }
        foreach ($links as $link) {
            $preceders = [...$preceders, ...$link->getNode()->getPreceders(true)];
        }
        return Utils::getUnique($preceders);
    }

    public function getPrecedeLinks(array $types = []): array
    {
        return array_filter(
            $this->preceders,
            fn(Link $link) => empty($types) || in_array($link->getType(), $types)
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
        if (!$withPreceders) {
            return $this->length;
        }
        $preceders = $this->getPreceders(true, $types);
        if (empty($preceders)) {
            return $this->length;
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $preceders)) - $this->getDistance() + $this->length;
    }

    public function getCompletion(): int
    {
        return $this->getDistance() - $this->getLength() + 1;
    }

    public function getSchedule(): array|string
    {
        return array_map(fn(Node $node) => [$node->getName(), $node->getLength(), $node->getDistance()], $this->getPreceders(true));
    }

    private function getLink(array $links, Node $node, string|null $type = null): Link|null
    {
        return array_reduce($links, fn(Link|null $acc, Link $link) => (
            $link->getNode() === $node && (is_null($type) || $link->getType() === $type)
        ) ? $link : $acc);
    }

    private function hasLink(array $links, Node $node, string|null $type = null): bool
    {
        return !is_null($this->getLink($links, $node, $type));
    }
}

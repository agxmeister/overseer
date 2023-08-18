<?php

namespace Watch\Schedule;

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

    public function __construct(private string $name, private int $length = 0)
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
     * @return Node[]
     */
    public function getFollowers(string|null $type = null): array
    {
        return array_map(
            fn(Link $link) => $link->getNode(),
            is_null($type) ?
                $this->followers :
                array_filter($this->followers, fn(Link $link) => $link->getType() === $type)
        );
    }

    /**
     * @param bool $isRecursively
     * @return Node[]
     */
    public function getPreceders(bool $isRecursively = false): array
    {
        $preceders = array_map(fn(Link $link) => $link->getNode(), $this->preceders);
        if (!$isRecursively) {
            return $preceders;
        }
        foreach ($this->preceders as $preceder) {
            $preceders = [...$preceders, ...$preceder->getNode()->getPreceders(true)];
        }
        $preceders = array_unique($preceders, SORT_REGULAR);
        usort($preceders, fn(Node $a, Node $b) => $a->getDistance() < $b->getDistance() ? -1 : ($a->getDistance() > $b->getDistance() ? 1 : 0));
        return $preceders;
    }

    public function getDistance(bool $withPreceders = false): int
    {
        if (count($this->followers) === 0) {
            return $this->getLength($withPreceders);
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $this->getFollowers())) + $this->getLength($withPreceders);
    }

    public function getLength(bool $withPreceders = false): int
    {
        if (!$withPreceders || empty($this->preceders)) {
            return $this->length;
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $this->getPreceders(true))) - $this->getDistance() + $this->length;
    }

    public function getCompletion(): int
    {
        return $this->getDistance() - $this->getLength() + 1;
    }

    public function getLongestPreceder(): Node|null
    {
        if (empty($this->preceders)) {
            return null;
        }
        return Utils::getLongestNode($this->getPreceders());
    }

    public function getShortestPreceder(): Node|null
    {
        if (empty($this->preceders)) {
            return null;
        }
        return Utils::getShortestNode($this->getPreceders());
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

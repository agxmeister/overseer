<?php

namespace Watch\Schedule;

class Node
{
    private array $followers = [];

    /**
     * @var Node[]
     */
    private array $preceders = [];

    public function __construct(private string $name, private int $length = 0)
    {
    }

    public function follow(Node $node): void
    {
        if (in_array($node, $this->preceders)) {
            return;
        }
        $this->preceders[] = $node;
        $node->precede($this);
    }

    public function unfollow(Node $node): void
    {
        if (!in_array($node, $this->preceders)) {
            return;
        }
        $this->preceders = array_filter($this->preceders, fn(Node $n) => $n !== $node);
        $node->unprecede($this);
    }

    public function precede(Node $node): void
    {
        if (in_array($node, $this->followers)) {
            return;
        }
        $this->followers[] = $node;
        $node->follow($this);
    }

    public function unprecede(Node $node): void
    {
        if (!in_array($node, $this->followers)) {
            return;
        }
        $this->followers = array_filter($this->followers, fn(Node $n) => $n !== $node);
        $node->unfollow($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPreceders(bool $isRecursively = false): array
    {
        if (!$isRecursively) {
            return $this->preceders;
        }
        $preceders = $this->preceders;
        foreach ($this->preceders as $preceder) {
            $preceders = array_merge($preceders, $preceder->getPreceders(true));
        }
        $preceders = array_unique($preceders);
        usort($preceders, fn(Node $a, Node $b) => $a->getDistance() < $b->getDistance() ? -1 : ($a->getDistance() > $b->getDistance() ? 1 : 0));
        return $preceders;
    }

    public function getDistance(bool $isRecursively = false): int
    {
        if (count($this->followers) === 0) {
            return $this->getLength($isRecursively);
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $this->followers)) + $this->getLength($isRecursively);
    }

    public function getLength(bool $isRecursively = false): int
    {
        if (!$isRecursively || empty($this->preceders)) {
            return $this->length;
        }
        return max(array_map(fn(Node $node) => $node->getDistance(), $this->getPreceders(true)));
    }

    public function getFinish(): int
    {
        return $this->getDistance() - $this->length;
    }

    public function getLongestPreceder(): Node
    {
        return array_reduce(
            $this->preceders,
            fn(Node|null $acc, Node $preceder) => is_null($acc) ? $preceder : ($acc->getDistance(true) < $preceder->getDistance(true) ? $preceder : $acc),
        );
    }

    public function getShortestPreceder(): Node
    {
        return array_reduce(
            $this->preceders,
            fn(Node|null $acc, Node $preceder) => is_null($acc) ? $preceder : ($acc->getDistance(true) > $preceder->getDistance(true) ? $preceder : $acc),
        );
    }

    public function getSchedule(): array|string
    {
        if (count($this->preceders) === 0) {
            return $this->getName();
        }
        return array_map(fn(Node $node) => $node->getSchedule(), $this->preceders);
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}

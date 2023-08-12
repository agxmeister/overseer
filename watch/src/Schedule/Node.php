<?php

namespace Watch\Schedule;

class Node
{
    private array $links = [];

    public function __construct(private string $name)
    {
    }

    public function link(Node $node): void
    {
        $this->links[] = new Link($node);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchedule(): array|string
    {
        if (!$this->links) {
            return $this->getName();
        }
        return array_map(fn($link) => $link->getNode()->getSchedule(), $this->links);
    }
}

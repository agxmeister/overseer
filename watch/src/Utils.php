<?php

namespace Watch;

class Utils
{
    static public function getUnique(array $items, callable $getKey): array
    {
        return array_values(array_reduce($items, fn($acc, $item) => [...$acc, "_{$getKey($item)}" => $item], []));
    }
}

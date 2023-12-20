<?php

namespace Watch;

readonly class Config
{
    private object $data;

    public function __construct($path)
    {
        $this->data = json_decode(file_get_contents($path));
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data->$name ?? $default;
    }
}

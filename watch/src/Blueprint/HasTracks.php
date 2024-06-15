<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Track;

trait HasTracks
{
    public function getLength(): int
    {
        $tracks = $this->getTracks();
        return
            array_reduce(
                $tracks,
                fn($acc, Track $track) => max($acc, strlen($track->content)),
                0
            ) -
            array_reduce(
                $tracks,
                fn($acc, Track $track) => min($acc, strlen($track->content) - strlen(rtrim($track->content))),
                PHP_INT_MAX
            ) -
            array_reduce(
                $tracks,
                fn($acc, Track $track) => min($acc, strlen($track->content) - strlen(ltrim($track->content))),
                PHP_INT_MAX
            );
    }

    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn($line) => $line->track,
            $this->getModelsWithTracks(),
        );
    }

    abstract protected function getModelsWithTracks();
}

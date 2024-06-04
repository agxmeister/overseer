<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Track;

trait HasTracks
{
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

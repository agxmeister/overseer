<?php

namespace Watch\Blueprint;

use Watch\Blueprint\Model\Track;
use Watch\Blueprint\Model\WithTrack;

trait HasTracks
{
    /**
     * @return Track[]
     */
    protected function getTracks(): array
    {
        return array_map(
            fn(WithTrack $line) => $line->track,
            array_values(
                array_filter(
                    $this->getModels(),
                    fn($line) => $line instanceof WithTrack,
                ),
            ),
        );
    }
}

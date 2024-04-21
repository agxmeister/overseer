<?php

namespace Watch\Description;

readonly abstract class TrackLine extends Line
{
    public Track $track;

    public function getEndPosition(): int
    {
        return strrpos($this->content, '|') - $this->track->gap;
    }

    protected function setTrack(string $trackContent): void
    {
        $this->track = new Track($trackContent);
    }
}

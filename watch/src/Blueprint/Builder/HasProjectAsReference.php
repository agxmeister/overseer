<?php

namespace Watch\Blueprint\Builder;

use Watch\Blueprint\Builder\Asset\Drawing;
use Watch\Blueprint\Builder\Asset\Parser;
use Watch\Blueprint\Model\Schedule\Milestone;

trait HasProjectAsReference
{
    public function setReference(Drawing $drawing): self
    {
        parent::setReference($drawing);

        if (!is_null($this->reference?->date)) {
            return $this;
        }

        $projectStroke = array_reduce(
            $this->milestoneModels,
            fn($acc, $line) => $line instanceof Milestone ? $line : null,
        );
        if (is_null($projectStroke)) {
            return $this;
        }

        $parser = new Parser($this->config->get('blueprint.drawing.stroke.pattern.reference'));
        $referenceStroke = $drawing->getStroke($parser);
        if (is_null($referenceStroke)) {
            $this->reference = new Reference(
                0,
                $projectStroke?->getDate()->modify("-{$this->projectMarkerOffset} day")
            );
        } else {
            $referenceMarkerOffset = $this->getReferenceMarkerOffset($referenceStroke);
            $gap = $referenceMarkerOffset - $this->projectMarkerOffset;
            $this->reference = new Reference(
                $referenceMarkerOffset,
                $projectStroke?->getDate()->modify("{$gap} day")
            );
        }

        return $this;
    }
}

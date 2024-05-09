<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Schedule as ScheduleBlueprintModel;

readonly class Schedule extends Blueprint
{
    public function create(string $content): ScheduleBlueprintModel
    {
        return new ScheduleBlueprintModel($content);
    }
}

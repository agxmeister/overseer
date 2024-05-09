<?php

namespace Watch\Blueprint\Factory;

use Watch\Blueprint\Subject as SubjectBlueprintModel;

readonly class Subject extends Blueprint
{
    public function create(string $content): SubjectBlueprintModel
    {
        return new SubjectBlueprintModel($content);
    }
}

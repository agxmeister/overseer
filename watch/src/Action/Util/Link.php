<?php

namespace Watch\Action\Util;

use Watch\Subject\Model\Link as SubjectLink;

class Link
{
    public function deserialize(array $attributes): SubjectLink
    {
        return new SubjectLink(...$attributes);
    }
}

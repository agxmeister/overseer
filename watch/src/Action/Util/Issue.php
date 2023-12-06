<?php

namespace Watch\Action\Util;

use Watch\Subject\Model\Issue as SubjectIssue;

class Issue
{
    public function serialize(SubjectIssue $issue): array
    {
        return array_filter(
            (array)$issue,
            fn($key) => in_array($key, ['key', 'summary', 'status', 'begin', 'end']),
            ARRAY_FILTER_USE_KEY
        );
    }
}

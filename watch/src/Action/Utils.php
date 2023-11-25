<?php

namespace Watch\Action;

use Watch\Issue;

class Utils
{
    public static function convertIssue(Issue $issue): array
    {
        return array_filter(
            (array)$issue,
            fn($key) => in_array($key, ['key', 'summary', 'status', 'begin', 'end']),
            ARRAY_FILTER_USE_KEY
        );
    }
}

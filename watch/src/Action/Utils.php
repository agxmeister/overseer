<?php

namespace Watch\Action;

class Utils
{
    public static function convertIssue(array $issue): array
    {
        return array_filter(
            $issue,
            fn($key) => in_array($key, ['key', 'summary', 'status', 'begin', 'end']),
            ARRAY_FILTER_USE_KEY
        );
    }
}

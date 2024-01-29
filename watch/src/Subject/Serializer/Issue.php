<?php

namespace Watch\Subject\Serializer;

use \Watch\Subject\Model\Issue as IssueModel;

class Issue
{
    public function serialize(IssueModel $issue): array
    {
        return array_filter(
            (array)$issue,
            fn($key) => in_array($key, ['key', 'summary', 'status', 'begin', 'end']),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function deserialize(array $attributes): IssueModel
    {
        return new IssueModel(...$attributes);
    }
}

<?php

namespace Watch\Schedule\Builder\Strategy\Convert;

use Watch\Config;
use Watch\Schedule\Builder\ConvertStrategy;
use Watch\Schedule\Model\Task;
use Watch\Subject\Model\Issue;

readonly class Plain implements ConvertStrategy
{
    public function __construct(private Config $config)
    {
    }

    public function getTask(Issue $issue): Task
    {
        return new Task($issue->key, $issue->duration, [
            'begin' => $issue->begin,
            'end' => $issue->end,
            ...array_reduce(
                $this->config->jira->statuses,
                fn($acc, $status) => [
                    ...$acc,
                    $status->state => $status->name === $issue->status,
                ],
                [],
            )
        ]);
    }
}

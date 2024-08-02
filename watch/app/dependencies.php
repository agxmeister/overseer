<?php

use DI\ContainerBuilder;
use Watch\Jira\Client as JiraClient;
use Watch\Config;
use Watch\Schedule\Mapper;

$config = json_decode(file_get_contents(__DIR__ . '/../config.json'));
$configDefaults = [
    'blueprint.drawing.stroke.pattern.key.attributes' => 'attributes',
];

return function (ContainerBuilder $containerBuilder) use ($config, $configDefaults) {
    $containerBuilder->addDefinitions([
        JiraClient::class => DI\autowire()->constructor(
            $_ENV['JIRA_API_URL'],
            $_ENV['JIRA_API_USERNAME'],
            $_ENV['JIRA_API_TOKEN'],
        ),
        Config::class => DI\autowire()->constructor($config, $configDefaults),
        Mapper::class => DI\autowire()->constructor(
            $config->schedule->task->state->queued,
            $config->schedule->task->state->started,
            $config->schedule->task->state->completed,
            $config->schedule->link->type->sequence,
            $config->schedule->link->type->schedule,
        ),
    ]);
};

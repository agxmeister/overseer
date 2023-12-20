<?php

use DI\ContainerBuilder;
use Watch\Config;
use Watch\Jira\Client as JiraClient;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Config::class => DI\autowire()->constructor(__DIR__ . '/../config.json'),
        JiraClient::class => DI\autowire()->constructor(
            $_ENV['JIRA_API_URL'],
            $_ENV['JIRA_API_USERNAME'],
            $_ENV['JIRA_API_TOKEN']
        ),
    ]);
};

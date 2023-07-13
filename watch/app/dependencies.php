<?php

use DI\ContainerBuilder;
use Watch\Jira;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        Jira::class => DI\autowire()
            ->constructor(
                $_ENV['JIRA_API_URL'],
                $_ENV['JIRA_API_USERNAME'],
                $_ENV['JIRA_API_TOKEN']
            )
    ]);
};

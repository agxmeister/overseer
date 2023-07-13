<?php

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$env = Dotenv::createImmutable(__DIR__ . '/../');
$env->load();
$env->required(['JIRA_API_URL', 'JIRA_API_USERNAME', 'JIRA_API_TOKEN']);

$dependencies = require __DIR__ . '/../app/dependencies.php';
$containerBuilder = new ContainerBuilder();
$dependencies($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$app->run();

<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dependencies = require __DIR__ . '/../app/dependencies.php';
$containerBuilder = new ContainerBuilder();
$dependencies($containerBuilder);
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$app->run();

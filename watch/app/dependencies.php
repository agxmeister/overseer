<?php

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions(__DIR__ . '/../config.php');
};

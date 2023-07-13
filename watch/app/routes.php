<?php

use Slim\App;
use Watch\Action\Hello;

return function (App $app) {
    $app->get('/api/v1/hello', Hello::class);
};

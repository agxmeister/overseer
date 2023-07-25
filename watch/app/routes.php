<?php

use Slim\App;
use Watch\Action\Hello;
use Watch\Action\Preflight;
use Watch\Action\SetStartDate;

return function (App $app) {
    $app->get('/api/v1/hello', Hello::class);
    $app->options('/api/v1/set-start-date', Preflight::class);
    $app->post('/api/v1/set-start-date', SetStartDate::class);
};

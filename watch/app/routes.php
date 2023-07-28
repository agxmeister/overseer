<?php

use Slim\App;
use Watch\Action\Tasks;
use Watch\Action\Preflight;
use Watch\Action\SetStartDate;
use Watch\Action\SetFinishDate;

return function (App $app) {
    $app->get('/api/v1/tasks', Tasks::class);
    $app->options('/api/v1/set-start-date', Preflight::class);
    $app->post('/api/v1/set-start-date', SetStartDate::class);
    $app->options('/api/v1/set-finish-date', Preflight::class);
    $app->post('/api/v1/set-finish-date', SetFinishDate::class);
};

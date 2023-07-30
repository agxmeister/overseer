<?php

use Slim\App;
use Watch\Action\Links;
use Watch\Action\Tasks;
use Watch\Action\Task;
use Watch\Action\Preflight;

return function (App $app) {
    $app->get('/api/v1/tasks', Tasks::class);
    $app->options('/api/v1/task/{taskId}', Preflight::class);
    $app->post('/api/v1/task/{taskId}', Task::class);
    $app->options('/api/v1/links', Preflight::class);
    $app->post('/api/v1/links', Links::class);
};

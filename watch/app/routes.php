<?php

use Slim\App;
use Watch\Action\CreateMilestone;
use Watch\Action\GetSchedule;
use Watch\Action\Link;
use Watch\Action\Links;
use Watch\Action\PutSchedule;
use Watch\Action\Tasks;
use Watch\Action\Task;
use Watch\Action\Preflight;

return function (App $app) {
    $app->get('/api/v1/tasks', Tasks::class);
    $app->post('/api/v1/milestones', CreateMilestone::class);
    $app->options('/api/v1/task/{taskId}', Preflight::class);
    $app->post('/api/v1/task/{taskId}', Task::class);
    $app->options('/api/v1/links', Preflight::class);
    $app->post('/api/v1/links', Links::class);
    $app->options('/api/v1/link/{from}/{to}/{type}', Preflight::class);
    $app->delete('/api/v1/link/{from}/{to}/{type}', Link::class);
    $app->get('/api/v1/schedule', GetSchedule::class);
    $app->options('/api/v1/schedule', Preflight::class);
    $app->put('/api/v1/schedule', PutSchedule::class);
};

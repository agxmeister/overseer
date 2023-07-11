<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/api/v1/hello', function (Request $request, Response $response, $args) use ($app) {
        $response->getBody()->write(json_encode($app->getContainer()->get('hello')));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });
};

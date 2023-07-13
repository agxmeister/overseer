<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;

class Hello
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $data = $this->jira->getByJql('');
        $tasks = [];
        foreach ($data->issues as $issueData) {
            $tasks[] = $issueData->fields->summary;
        }
        $response->getBody()->write(json_encode($tasks));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

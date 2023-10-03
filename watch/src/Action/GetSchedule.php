<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Director;

class GetSchedule
{
    public function __construct(private Jira $jira, private Director $director)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $schedule = $this->director->get($this->jira->getIssues(''));
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Strategy\Basic;

class Schedule
{
    public function __construct(private Jira $jira, private Builder $builder)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $strategy = new Basic();
        $issues = $this->builder->getSchedule($this->jira->getIssues(''), $params->date, $strategy);
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

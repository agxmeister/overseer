<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder;

class Schedule
{
    public function __construct(private Jira $jira, private Builder $builder)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $issues = $this->builder->getSchedule($this->jira->getIssues(''), "2023-08-31");
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

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
        $tree = $this->builder->getGraph($this->jira->getIssues(''));
        $response->getBody()->write(json_encode($tree->getSchedule()));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

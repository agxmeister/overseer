<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Preserving;
use Watch\Schedule\Director;
use Watch\Subject\Adapter;

class GetSchedule
{
    public function __construct(private readonly Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $director = new Director(new Preserving(
            new Context(new \DateTimeImmutable(date('Y-m-d')), new Adapter()),
            $this->jira->getIssues(''),
        ));
        $schedule = $director->build()->release();
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

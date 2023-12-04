<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Formatter;
use Watch\Subject\Adapter;

class GetSchedule
{
    public function __construct(private readonly Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d')), new Adapter()),
                $this->jira->getIssues(''),
            )
        );
        $adapter = new Formatter();
        $schedule = $adapter->getSchedule($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

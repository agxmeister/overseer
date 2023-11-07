<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\FromExisting;
use Watch\Schedule\Builder\Strategy\Schedule\LeftToRight;
use Watch\Schedule\Director;

class GetSchedule
{
    public function __construct(private readonly Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $director = new Director(new FromExisting(
            new Context(new \DateTimeImmutable(date('Y-m-d'))),
            $this->jira->getIssues(''),
            new LeftToRight(),
        ));
        $schedule = $director->build()->release();
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

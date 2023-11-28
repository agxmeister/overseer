<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Modifying;
use Watch\Schedule\Builder\Strategy\Limit\Initiative;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate;
use Watch\Schedule\Director;
use Watch\Subject\Adapter;

class PutSchedule
{
    public function __construct(private readonly Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $director = new Director(
            new Modifying(
                new Context(new \DateTimeImmutable(date('Y-m-d')), new Adapter()),
                $this->jira->getIssues(''),
                new Initiative(2),
                new ToDate(new \DateTimeImmutable($params->date)),
            )
        );
        $issues = $director->build()->release();
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

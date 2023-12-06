<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Schedule as Util;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Initiative;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate;
use Watch\Schedule\Director;
use Watch\Subject\Adapter;

readonly class PutSchedule
{
    public function __construct(private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d')), new Adapter()),
                $this->jira->getIssues(''),
                new Initiative(2),
                new ToDate(new \DateTimeImmutable($params->date)),
            )
        );
        $schedule = $this->util->serialize($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

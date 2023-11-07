<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\FromScratch;
use Watch\Schedule\Builder\Strategy\Limit\Basic;
use Watch\Schedule\Builder\Strategy\Schedule\RightToLeft;
use Watch\Schedule\Director;

class PutSchedule
{
    public function __construct(private readonly Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $director = new Director(
            new FromScratch(
                new Context(new \DateTimeImmutable(date('Y-m-d'))),
                $this->jira->getIssues(''),
                new RightToLeft(new \DateTimeImmutable($params->date)),
                new Basic(),
            )
        );
        $issues = $director->build()->release();
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

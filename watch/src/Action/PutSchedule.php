<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Schedule\Strategy\Limit\Basic;
use Watch\Schedule\Strategy\Schedule\LateStart;
use Watch\Jira;
use Watch\Schedule\Director;

class PutSchedule
{
    public function __construct(private readonly Jira $jira, private readonly Director $director)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $limitStrategy = new Basic();
        $scheduleStrategy = new LateStart(new \DateTimeImmutable($params->date));
        $issues = $this->director->create($this->jira->getIssues(''), $limitStrategy, $scheduleStrategy);
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

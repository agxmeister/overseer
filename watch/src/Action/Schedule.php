<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Director;
use Watch\Schedule\Formatter;
use Watch\Schedule\Strategy\Basic;

class Schedule
{
    public function __construct(private Jira $jira, private Director $director)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $strategy = new Basic();
        $formatter = new Formatter();
        $issues = $this->director->create($this->jira->getIssues(''), $params->date, $strategy, $formatter);
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

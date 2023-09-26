<?php

namespace Watch\Action;

use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Director;
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
        $issues = $this->director->create($this->jira->getIssues(''), new DateTime($params->date), $strategy);
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

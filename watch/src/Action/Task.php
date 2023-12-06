<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Issue as Util;
use Watch\Jira;

readonly class Task
{
    public function __construct(private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $taskId = $args['taskId'];
        $params = json_decode(file_get_contents('php://input'), true);
        $this->jira->setIssue($taskId, $params);
        $response->getBody()->write(json_encode($this->util->serialize($this->jira->getIssue($taskId))));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

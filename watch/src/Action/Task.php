<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Issue as Util;
use Watch\Jira;
use Watch\Subject\Model\Issue;

readonly class Task
{
    public function __construct(private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $taskId = $args['taskId'];
        $params = json_decode(file_get_contents('php://input'), true);
        $issue = new Issue(...[
            'key' => $taskId,
            ...$params,
        ]);
        $this->jira->setIssue($issue);
        $response->getBody()->write(json_encode($this->util->serialize($this->jira->getIssue($taskId))));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

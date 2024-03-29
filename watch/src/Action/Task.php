<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Subject\Serializer\Issue as IssueSerializer;

readonly class Task
{
    public function __construct(private Jira $jira, private IssueSerializer $issueSerializer)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $taskId = $args['taskId'];
        $attributes = json_decode(file_get_contents('php://input'), true);
        $this->jira->updateIssue($taskId, $attributes);
        $response->getBody()->write(json_encode($this->issueSerializer->serialize($this->jira->getIssue($taskId))));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

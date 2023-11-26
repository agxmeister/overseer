<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Subject\Model\Issue;
use Watch\Jira;

class Tasks
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $response->getBody()->write(json_encode(
            array_map(
                fn(Issue $issue) => Utils::convertIssue($issue),
                $this->jira->getIssues('')
            ),
        ));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

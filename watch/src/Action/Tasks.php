<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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
                fn($issue) => array_filter(
                    $issue,
                    fn($key) => in_array($key, ['key', 'summary', 'begin', 'end']),
                    ARRAY_FILTER_USE_KEY
                ),
                $this->jira->getIssues('')
            ),
        ));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

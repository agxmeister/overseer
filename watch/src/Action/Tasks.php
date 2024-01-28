<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Subject\Model\Issue;
use Watch\Action\Util\Issue as Util;
use Watch\Jira;

readonly class Tasks
{
    public function __construct(private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $response->getBody()->write(json_encode(
            array_map(
                fn(Issue $issue) => $this->util->serialize($issue),
                $this->jira->getSubject('')->issues,
            ),
        ));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Subject\Model\Issue as IssueModel;
use Watch\Subject\Serializer\Issue as IssueSerializer;

readonly class Tasks
{
    public function __construct(private Jira $jira, private IssueSerializer $issueSerializer)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $response->getBody()->write(json_encode(
            array_map(
                fn(IssueModel $issue) => $this->issueSerializer->serialize($issue),
                $this->jira->getSubject('')->issues,
            ),
        ));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Description\Utils as DescriptionUtils;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class CreateMilestone
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $description = file_get_contents('php://input');
        $issues = DescriptionUtils::getIssues(
            $description,
            fn($started, $completed) => [
                'status' => $started ? 'In Progress' : ($completed ? 'Done' : 'To Do'),
            ]
        );
        $links = DescriptionUtils::getLinks($description);

        $issueIds = array_reduce(
            $issues,
            fn(array $acc, Issue $issue) => [
                ...$acc,
                $issue->key => $this->jira->createIssue(get_object_vars($issue)),
            ],
            [],
        );
        array_reduce(
            $links,
            fn($acc, Link $link) => $this->jira->addLink(
                $issueIds[$link->from],
                $issueIds[$link->to],
                $link->type
            ),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

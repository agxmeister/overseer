<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Description\Utils as DescriptionUtils;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Joint;

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
        $joints = DescriptionUtils::getJoints($description);

        $issueIds = array_reduce(
            $issues,
            fn(array $acc, Issue $issue) => [
                ...$acc,
                $issue->key => $this->jira->createIssue(get_object_vars($issue)),
            ],
            [],
        );
        array_reduce(
            $joints,
            fn($acc, Joint $joint) => $this->jira->addLink(
                $issueIds[$joint->from],
                $issueIds[$joint->to],
                $joint->type
            ),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

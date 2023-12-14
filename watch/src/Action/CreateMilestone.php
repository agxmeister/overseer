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
        $issues = DescriptionUtils::getIssues($description);

        $keys = array_reduce(
            $issues,
            fn(array $acc, Issue $issue) => [...$acc, $issue->key => $this->jira->addIssue($issue)],
            [],
        );

        array_reduce(
            $issues,
            fn($acc, Issue $issue) => array_reduce(
                $issue->links,
                fn($acc, Link $link) => $this->jira->addLink(
                    $link->role === Link::ROLE_INWARD ? $keys[$issue->key] : $keys[$link->key],
                    $link->role === Link::ROLE_INWARD ? $keys[$link->key] : $keys[$issue->key],
                    $link->getType(),
                ),
            ),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

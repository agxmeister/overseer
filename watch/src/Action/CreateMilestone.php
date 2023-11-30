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
            fn(array $acc, Issue $issue) => [...$acc, $issue->key => $this->jira->createIssue($issue)],
            [],
        );

        $links = array_values(array_unique(array_reduce(
            $issues,
            fn($acc, Issue $issue) => [...$acc, ...array_map(
                fn(Link $link) => [
                    'from' => $keys[$link->role === Link::ROLE_INWARD ? $issue->key : $link->key],
                    'to' => $keys[$link->role === Link::ROLE_INWARD ? $link->key : $issue->key],
                    'type' => $link->type,
                ],
                $issue->links
            )],
            []
        ), SORT_REGULAR));
        foreach ($links as $link) {
            $this->jira->addLink($link['from'], $link['to'], $link['type']);
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

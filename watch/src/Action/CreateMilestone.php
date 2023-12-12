<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Action\Util\Issue as IssueUtil;
use Watch\Action\Util\Link as LinkUtil;
use Watch\Schedule\Description\Utils as DescriptionUtils;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class CreateMilestone
{
    public function __construct(private Jira $jira, private IssueUtil $issueUtil, private LinkUtil $linkUtil)
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

        $getIssue = fn(Issue $issue) => $this->issueUtil->deserialize([
            'key' => $keys[$issue->key],
        ]);
        $getLink = fn(Issue $issue, Link $link) => $this->linkUtil->deserialize([
            'key' => $keys[$link->key],
            'type' => $link->getType(),
            'role' => $link->role,
        ]);

        array_reduce(
            $issues,
            fn($acc, Issue $issue) => array_reduce(
                $issue->links,
                fn($acc, Link $link) => $this->jira->addLink(
                    $getIssue($issue),
                    $getLink($issue, $link)
                ),
            ),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

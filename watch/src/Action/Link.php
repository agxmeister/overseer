<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;

class Link
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $from = $args['from'];
        $to = $args['to'];
        $type = $args['type'];
        $issue = $this->jira->getIssueRaw($from);
        $linkId = array_reduce(
            $issue->fields->issuelinks,
            fn($acc, $link) => isset($link->inwardIssue) && $link->inwardIssue->key === $to && $link->type->name === $type ? $link->id : $acc,
        );
        $this->jira->removeLink($linkId);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

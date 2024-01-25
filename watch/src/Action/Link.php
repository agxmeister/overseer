<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Subject\Model\Link as SubjectLink;

readonly class Link
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $from = $args['from'];
        $to = $args['to'];
        $type = $args['type'];
        $link = array_reduce(
            $this->jira->getLinks($from),
            fn($acc, SubjectLink $link) =>
                $link->from === $from &&
                $link->to === $to &&
                $link->type === $type
                    ? $link
                    : $acc,
        );
        $this->jira->removeLink($link->id);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

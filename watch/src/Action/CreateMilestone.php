<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Blueprint\Builder\Context;
use Watch\Blueprint\Builder\Director;
use Watch\Blueprint\Builder\Drawing;
use Watch\Blueprint\Builder\Subject as SubjectBlueprintBuilder;
use Watch\Jira;
use Watch\Schedule\Mapper;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class CreateMilestone
{
    public function __construct(private Jira $jira, private Mapper $mapper)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $blueprintBuilder = new SubjectBlueprintBuilder(
            new Drawing(file_get_contents('php://input')),
            new Context(),
            $this->mapper,
        );
        $blueprintDirector = new Director();
        $blueprintDirector->build($blueprintBuilder);
        $blueprint = $blueprintBuilder->flush();

        $issueIds = array_reduce(
            $blueprint->getIssues($this->mapper),
            fn(array $acc, Issue $issue) => [
                ...$acc,
                $issue->key => $this->jira->createIssue(get_object_vars($issue)),
            ],
            [],
        );
        array_reduce(
            $blueprint->getLinks(),
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

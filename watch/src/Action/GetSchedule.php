<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProjectSerializer;

readonly class GetSchedule
{
    public function __construct(private Jira $jira, private Mapper $mapper, private ProjectSerializer $projectSerializer)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $subject = $this->jira->getSubject('');
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d'))),
                $subject->issues,
                $subject->links,
                'PRJ',
                ['M1'],
                $this->mapper,
            )
        );
        $project = $this->projectSerializer->serialize($director->build()->release()->getProject());
        $response->getBody()->write(json_encode($project));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

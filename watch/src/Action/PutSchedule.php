<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Initiative as InitiativeLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Project as ProejctSerializer;

readonly class PutSchedule
{
    public function __construct(private Jira $jira, private Mapper $mapper, private ProejctSerializer $projectSerializer)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $subject = $this->jira->getSubject('');
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d'))),
                $subject->issues,
                $subject->links,
                'PRJ',
                ['M1'],
                $this->mapper,
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy(new \DateTimeImmutable($params->date)),
            )
        );
        $project = $this->projectSerializer->serialize($director->build()->release()->getProject());
        $response->getBody()->write(json_encode($project));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

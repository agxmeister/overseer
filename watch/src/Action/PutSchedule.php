<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Config;
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
    public function __construct(private Config $config, private Jira $jira, private ProejctSerializer $projectSerializer)
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
                ['M2', 'M1'],
                new Mapper(
                    $this->config->schedule->task->state->started,
                    $this->config->schedule->task->state->completed,
                    $this->config->schedule->link->type->sequence,
                    $this->config->schedule->link->type->schedule,
                ),
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy(new \DateTimeImmutable($params->date)),
            )
        );
        $project = $this->projectSerializer->serialize($director->build()->release()->project);
        $response->getBody()->write(json_encode($project));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

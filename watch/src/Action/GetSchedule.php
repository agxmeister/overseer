<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Config;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;
use Watch\Schedule\Serializer\Schedule as ScheduleSerializer;

readonly class GetSchedule
{
    public function __construct(private Config $config, private Jira $jira, private ScheduleSerializer $scheduleSerializer)
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
                ['finish'],
                new Mapper(
                    $this->config->schedule->task->state->started,
                    $this->config->schedule->task->state->completed,
                    $this->config->schedule->link->type->sequence,
                    $this->config->schedule->link->type->schedule,
                ),
            )
        );
        $schedule = $this->scheduleSerializer->serialize($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

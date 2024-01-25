<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Schedule as Util;
use Watch\Config;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;

readonly class GetSchedule
{
    public function __construct(private Config $config, private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $sample = $this->jira->getIssues('');
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d'))),
                $sample->issues,
                $sample->links,
                ['finish'],
                new Mapper(
                    $this->config->schedule->task->state->started,
                    $this->config->schedule->task->state->completed,
                    $this->config->schedule->link->type->sequence,
                    $this->config->schedule->link->type->schedule,
                ),
            )
        );
        $schedule = $this->util->serialize($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

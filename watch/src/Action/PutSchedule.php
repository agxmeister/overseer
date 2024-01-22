<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Schedule as Util;
use Watch\Config;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\Limit\Initiative as InitiativeLimitStrategy;
use Watch\Schedule\Builder\Strategy\Schedule\ToDate as ToDateScheduleStrategy;
use Watch\Schedule\Builder\Strategy\Convert\Plain as PlainConvertStrategy;
use Watch\Schedule\Director;
use Watch\Schedule\Mapper;

readonly class PutSchedule
{
    public function __construct(private Config $config, private Jira $jira, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));
        $sample = $this->jira->getIssues('');
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d'))),
                $sample->issues,
                $sample->joints,
                ['finish'],
                new Mapper(
                    $this->config->schedule->link->type->sequence->joints,
                    $this->config->schedule->link->type->schedule->joints,
                    $this->config->schedule->state->started,
                    $this->config->schedule->state->completed,
                ),
                new PlainConvertStrategy($this->config),
                new InitiativeLimitStrategy(2),
                new ToDateScheduleStrategy(new \DateTimeImmutable($params->date)),
            )
        );
        $schedule = $this->util->serialize($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

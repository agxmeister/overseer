<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Schedule as Util;
use Watch\Jira;
use Watch\Schedule\Builder;
use Watch\Schedule\Builder\Context;
use Watch\Schedule\Builder\Strategy\State\MapByStatus as MapByStatusStateStrategy;
use Watch\Schedule\Director;
use Watch\Subject\Decorator\Factory;
use Watch\Subject\Model\Issue;

readonly class GetSchedule
{
    public function __construct(private Jira $jira, private Factory $factory, private Util $util)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $issues = $this->jira->getIssues('');
        $director = new Director(
            new Builder(
                new Context(new \DateTimeImmutable(date('Y-m-d')), $this->factory),
                $issues,
                ['finish'],
                new MapByStatusStateStrategy(),
            )
        );
        $schedule = $this->util->serialize($director->build()->release());
        $response->getBody()->write(json_encode($schedule));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

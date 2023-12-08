<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;
use Watch\Schedule\Model\Task;
use Watch\Schedule\Decorator\Factory;

class Links
{
    public function __construct(private readonly Jira $jira, private readonly Factory $factory)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));

        $from = new Task($params->outwardTaskId);
        $to = new Task($params->inwardTaskId);
        $to->follow($from, $params->type);
        $link = current($from->getFollowLinks());

        $this->jira->addLink(
            $params->outwardTaskId,
            $params->inwardTaskId,
            $this->factory->getLink($link)->getType(),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

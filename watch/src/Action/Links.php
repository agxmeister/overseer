<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Action\Util\Issue as IssueUtil;
use Watch\Action\Util\Link as LinkUtil;
use Watch\Jira;
use Watch\Schedule\Model\Task;
use Watch\Schedule\Decorator\Factory;
use Watch\Subject\Model\Link;

readonly class Links
{
    public function __construct(
        private Jira $jira,
        private IssueUtil $issueUtil,
        private LinkUtil $linkUtil,
        private Factory $factory
    )
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
            $this->issueUtil->deserialize([
                'key' => $params->outwardTaskId,
            ]),
            $this->linkUtil->deserialize([
                'key' => $params->inwardTaskId,
                'type' => $this->factory->getLink($link)->getType(),
                'role' => Link::ROLE_INWARD,
            ]),
        );

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

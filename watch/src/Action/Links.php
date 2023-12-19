<?php

namespace Watch\Action;

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;

readonly class Links
{
    public function __construct(private Jira $jira)
    {
    }

    /**
     * @throws GuzzleException
     */
    public function __invoke(Request $request, Response $response, $args): Response
    {
        $params = json_decode(file_get_contents('php://input'));

        $linkId = $this->jira->addLink(
            $params->outwardTaskId,
            $params->inwardTaskId,
            $params->type,
        );

        $response->getBody()->write(json_encode($linkId));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}

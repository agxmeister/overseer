<?php

namespace Watch\Action;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Watch\Jira;

class Hello
{
    public function __construct(private Jira $jira)
    {
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $data = $this->jira->getByJql('');
        $issues = [];
        foreach ($data->issues as $issueData) {
            $issues[] = [
                'key' => $issueData->key,
                'summary' => $issueData->fields->summary,
                'estimatedStartDate' => $issueData->fields->customfield_10036,
                'estimatedFinishDate' => $issueData->fields->customfield_10037,
            ];
        }
        $response->getBody()->write(json_encode($issues));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}

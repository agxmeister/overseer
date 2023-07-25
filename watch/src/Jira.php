<?php

namespace Watch;

use GuzzleHttp\Client;
class Jira
{
    private Client $client;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
    }

    public function getIssue($jiraId): string
    {
        $response = $this->getClient()->get("issue/$jiraId");
        $data = json_decode($response->getBody());
        return $data->fields->summary;
    }

    public function getByJql($jql): mixed
    {
        $response = $this->getClient()->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ]);
        return json_decode($response->getBody());
    }

    public function setStartDate($jiraId, $startDate): void
    {
        $this->getClient()->put("issue/$jiraId", [
            'json' => [
                'fields' => [
                    'customfield_10037' => $startDate,
                ],
            ],
        ]);
    }

    private function getClient(): Client
    {
        return $this->client = $this->client ?? new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiUsername . ':' . $this->apiToken),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}

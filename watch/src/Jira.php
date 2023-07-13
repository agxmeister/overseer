<?php

namespace Watch;

use GuzzleHttp\Client;

class Jira
{
    private Client $client;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
    }

    public function getIssue(): string
    {
        $response = $this->getClient()->get('issue/OD-1', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiUsername . ':' . $this->apiToken),
                'Content-Type' => 'application/json',
            ],
        ]);
        $data = json_decode($response->getBody());
        return $data->fields->summary;
    }

    private function getClient(): Client
    {
        return $this->client = $this->client ?? new Client([
            'base_uri' => $this->apiUrl,
        ]);
    }
}

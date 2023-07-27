<?php

namespace Watch;

use GuzzleHttp\Client;
class Jira
{
    private Client $client;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
    }

    public function getIssue($jiraId): array
    {
        $response = $this->getClient()->get("issue/$jiraId");
        $issueData = json_decode($response->getBody());
        return $this->formatIssue($issueData);
    }

    public function getIssues($jql): mixed
    {
        $response = $this->getClient()->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ]);
        $data = json_decode($response->getBody());
        $issues = [];
        foreach ($data->issues as $issueData) {
            $issues[] = $this->formatIssue($issueData);
        }
        return $issues;
    }

    public function setStartDate($jiraId, $startDate): void
    {
        $this->getClient()->put("issue/$jiraId", [
            'json' => [
                'fields' => [
                    'customfield_10036' => $startDate,
                ],
            ],
        ]);
    }

    private function formatIssue($issue): array
    {
        return [
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'estimatedStartDate' => $issue->fields->customfield_10036,
            'estimatedFinishDate' => $issue->fields->customfield_10037,
        ];
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

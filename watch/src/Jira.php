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

    public function setIssue($jiraId, $fields): void
    {
        $jiraFields = [];
        foreach ($fields as $key => $value) {
            $jiraFields[$this->fieldsMapping($key)] = $value;
        }
        $this->getClient()->put("issue/$jiraId", [
            'json' => [
                'fields' => $jiraFields,
            ],
        ]);
    }

    public function addLink($outwardJiraId, $inwardJiraId, $type)
    {
        $this->getClient()->post("issueLink", [
            'json' => [
                'inwardIssue' => [
                    'key' => $outwardJiraId,
                ],
                'outwardIssue' => [
                    'key' => $inwardJiraId,
                ],
                'type' => [
                    'name' => $type,
                ],
            ],
        ]);
    }

    private function formatIssue($issue): array
    {
        $links = [
            'inward' => [],
            'outward' => [],
        ];
        foreach ($issue->fields->issuelinks as $link) {
            if (isset($link->outwardIssue)) {
                $links['outward'][] = [
                    'key' => $link->outwardIssue->key,
                    'type' => $link->type->name,
                ];
            } else if (isset($link->inwardIssue)) {
                $links['inward'][] = [
                    'key' => $link->inwardIssue->key,
                    'type' => $link->type->name,
                ];
            }
        }
        $estimatedStartDate = $issue->fields->customfield_10036 ?? date('Y-m-d');
        $estimatedFinishDate =
            $issue->fields->customfield_10037 &&
            $issue->fields->customfield_10037 >= $estimatedStartDate ?
                $issue->fields->customfield_10037 :
                $estimatedStartDate;
        return [
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'estimatedDuration' => (int)$issue->fields->customfield_10038,
            'estimatedStartDate' =>$estimatedStartDate,
            'estimatedFinishDate' => $estimatedFinishDate,
            'links' => $links,
        ];
    }

    private function fieldsMapping($field): string
    {
        $mapping = [
            "estimatedStartDate" => "customfield_10036",
            "estimatedFinishDate" => "customfield_10037",
        ];
        return $mapping[$field];
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

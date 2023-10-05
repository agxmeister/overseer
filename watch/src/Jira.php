<?php

namespace Watch;

use GuzzleHttp\Client;
use Watch\Schedule\Model\Link;

class Jira
{
    private Client $client;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
    }

    public function getIssue($jiraId): array
    {
        $response = $this->getClient()->get("issue/$jiraId");
        return $this->convert(json_decode($response->getBody()));
    }

    public function getIssues($jql): mixed
    {
        $response = $this->getClient()->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ]);
        return array_map(fn($issueRaw) => $this->convert($issueRaw), json_decode($response->getBody())->issues);
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

    public function addLink($outwardJiraId, $inwardJiraId, $type): void
    {
        $this->getClient()->post("issueLink", [
            'json' => [
                'outwardIssue' => [
                    'key' => $outwardJiraId,
                ],
                'inwardIssue' => [
                    'key' => $inwardJiraId,
                ],
                'type' => [
                    'name' => $this->getLinkNameByType($type),
                ],
            ],
        ]);
    }

    public function removeLink($linkId): void
    {
        $this->getClient()->delete("issueLink/$linkId");
    }

    private function convert($issue): array
    {
        $begin = $issue->fields->customfield_10036 ?? date('Y-m-d');
        $end =
            $issue->fields->customfield_10037 &&
            $issue->fields->customfield_10037 >= $begin ?
                $issue->fields->customfield_10037 :
                $begin;
        return [
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'duration' => (int)$issue->fields->customfield_10038,
            'begin' => $begin,
            'end' => $end,
            'links' => [
                'outward' => array_values(array_map(
                    fn($link) => [
                        'id' => $link->id,
                        'key' => $link->outwardIssue->key,
                        'type' => $this->getLinkTypeByName($link->type->name),
                    ],
                    array_filter(
                        $issue->fields->issuelinks,
                        fn($link) => isset($link->outwardIssue)
                    )
                )),
                'inward' => array_values(array_map(
                    fn($link) => [
                        'id' => $link->id,
                        'key' => $link->inwardIssue->key,
                        'type' => $this->getLinkTypeByName($link->type->name),
                    ],
                    array_filter(
                        $issue->fields->issuelinks,
                        fn($link) => isset($link->inwardIssue)
                    )
                )),
            ],
        ];
    }

    private function fieldsMapping($field): string
    {
        $mapping = [
            "begin" => "customfield_10036",
            "end" => "customfield_10037",
        ];
        return $mapping[$field];
    }

    private function getLinkTypeByName($name): string
    {
        return $name === 'Depends' ? Link::TYPE_SEQUENCE : Link::TYPE_SCHEDULE;
    }

    private function getLinkNameByType($type): string
    {
        return $type === Link::TYPE_SEQUENCE ? 'Depends' : 'Follows';
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

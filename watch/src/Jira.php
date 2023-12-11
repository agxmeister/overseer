<?php

namespace Watch;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class Jira
{
    const DEFAULT_MILESTONE = 'finish';

    private Client $client;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
    }

    /**
     * @param $jiraId
     * @return Issue
     * @throws GuzzleException
     */
    public function getIssue($jiraId): Issue
    {
        $response = $this->getClient()->get("issue/$jiraId");
        return $this->convert(json_decode($response->getBody()));
    }

    /**
     * @param $jql
     * @return Issue[]
     * @throws GuzzleException
     */
    public function getIssues($jql): array
    {
        $response = $this->getClient()->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ]);
        return array_map(fn($issueRaw) => $this->convert($issueRaw), json_decode($response->getBody())->issues);
    }

    public function setIssue(Issue $issue): void
    {
        $this->getClient()->put("issue/$issue->key", [
            'json' => [
                'fields' => $this->getFields($issue),
            ],
        ]);
    }

    public function addIssue(Issue $issue): string
    {
        return json_decode($this->getClient()->post("issue", [
            'json' => [
                'fields' => [
                    'project' => [
                        'key' => 'OD',
                    ],
                    'issuetype' => [
                        'id' => '10001',
                    ],
                    ...$this->getFields($issue)
                ],
                ...($issue->started ?? false ? ['transition' => ['id' => 2]] : []),
                ...($issue->completed ?? false ? ['transition' => ['id' => 31]] : []),
            ],
        ])->getBody())->key;
    }

    public function addLink(Issue $issue, Link $link): void
    {
        $this->getClient()->post("issueLink", [
            'json' => [
                'outwardIssue' => [
                    'key' => $link->role === Link::ROLE_INWARD ? $issue->key : $link->key,
                ],
                'inwardIssue' => [
                    'key' => $link->role === Link::ROLE_OUTWARD ? $issue->key : $link->key,
                ],
                'type' => [
                    'name' => $link->getType(),
                ],
            ],
        ]);
    }

    public function removeLink($linkId): void
    {
        $this->getClient()->delete("issueLink/$linkId");
    }

    private function convert($issue): Issue
    {
        $status = $issue->fields->status->name;
        $begin = $issue->fields->customfield_10036 ?? date('Y-m-d');
        $end =
            $issue->fields->customfield_10037 &&
            $issue->fields->customfield_10037 >= $begin ?
                $issue->fields->customfield_10037 :
                $begin;
        return new Issue(...[
            'id' => $issue->id,
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'status' => $status,
            'duration' => (int)$issue->fields->customfield_10038,
            'begin' => $begin,
            'end' => $end,
            'started' => $this->isStarted($status),
            'completed' => $this->isCompleted($status),
            'links' => [
                ...array_values(array_map(
                    fn($link) => new Link(
                        $link->id,
                        $link->outwardIssue->key,
                        $link->type->name,
                        Link::ROLE_OUTWARD,
                    ),
                    array_filter(
                        $issue->fields->issuelinks,
                        fn($link) => isset($link->outwardIssue)
                    )
                )),
                ...array_values(array_map(
                    fn($link) => new Link(
                        $link->id,
                        $link->inwardIssue->key,
                        $link->type->name,
                        Link::ROLE_INWARD,
                    ),
                    array_filter(
                        $issue->fields->issuelinks,
                        fn($link) => isset($link->inwardIssue)
                    )
                )),
            ],
            'milestone' => self::DEFAULT_MILESTONE,
        ]);
    }

    private function getIssueFieldsMap(): array
    {
        return [
            "summary" => "summary",
            "duration" => "customfield_10038",
            "begin" => "customfield_10036",
            "end" => "customfield_10037",
        ];
    }

    private function getFields(Issue $issue): array
    {
        $issueFieldMap = $this->getIssueFieldsMap();
        return array_filter(
            array_reduce(
                array_map(
                    fn(string $subjectField, $jiraField) => [
                        $jiraField,
                        $issue->$subjectField ?? null,
                    ],
                    array_keys($issueFieldMap),
                    array_values($issueFieldMap),
                ),
                fn($acc, $field) => [
                    ...$acc,
                    $field[0] => $field[1]
                ],
                [],
            ),
            fn($value) => !is_null($value),
        );
    }

    private function isStarted(string $status): bool
    {
        return $status === 'In Progress';
    }

    private function isCompleted(string $status): bool
    {
        return $status === 'Done';
    }

    private function getClient(): Client
    {
        if (!isset($this->client)) {
            $this->client = new Client([
                'base_uri' => $this->apiUrl,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->apiUsername . ':' . $this->apiToken),
                    'Content-Type' => 'application/json',
                ],
            ]);
        }
        return $this->client;
    }
}

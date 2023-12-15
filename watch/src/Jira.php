<?php

namespace Watch;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class Jira
{
    const DEFAULT_MILESTONE = 'finish';

    const STATUS_STARTED = ['In Progress'];
    const STATUS_COMPLETED = ['Done'];
    const FIELDS_MAP_ISSUE = [
        "summary" => "summary",
        "duration" => "customfield_10038",
        "begin" => "customfield_10036",
        "end" => "customfield_10037",
    ];

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
        return $this->getIssueByFields(json_decode($response->getBody()));
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
        return array_map(fn($issueRaw) => $this->getIssueByFields($issueRaw), json_decode($response->getBody())->issues);
    }

    public function setIssue(int|string $issueId, array $attributes): void
    {
        $this->getClient()->put("issue/$issueId", [
            'json' => [
                'fields' => $this->getIssueFieldsByAttributes($attributes),
            ],
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function addIssue(array $attributes): string
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
                    ...$this->getIssueFieldsByAttributes($attributes)
                ],
                ...($attributes['started'] ?? false ? ['transition' => ['id' => 2]] : []),
                ...($attributes['completed'] ?? false ? ['transition' => ['id' => 31]] : []),
            ],
        ])->getBody())->id;
    }

    /**
     * @throws GuzzleException
     */
    public function addLink(int|string $fromIssueId, int|string $toIssueId, string $type): int
    {
        $this->getClient()->post("issueLink", [
            'json' => [
                'outwardIssue' => [
                    'id' => $fromIssueId,
                    'key' => $fromIssueId,
                ],
                'inwardIssue' => [
                    'id' => $toIssueId,
                    'key' => $toIssueId,
                ],
                'type' => [
                    'name' => $type,
                ],
            ],
        ]);

        return array_reduce(
            json_decode(
                $this
                    ->getClient()
                    ->get("issue/$fromIssueId?fields=issuelinks")
                    ->getBody()
            )
                ->fields
                ->issuelinks,
            fn($acc, $link) => (
                isset($link->inwardIssue)
                && ($link->inwardIssue->id === $toIssueId || $link->inwardIssue->key === $toIssueId)
                && $link->type->name === $type
            )
                ? $link->id
                : $acc
        );
    }

    public function removeLink($linkId): void
    {
        $this->getClient()->delete("issueLink/$linkId");
    }

    private function getIssueByFields($issue): Issue
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
            'milestone' => self::DEFAULT_MILESTONE,
            'started' => in_array($status, self::STATUS_STARTED),
            'completed' => in_array($status, self::STATUS_COMPLETED),
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
        ]);
    }

    private function getIssueFieldsByAttributes(array $attributes): array
    {
        return array_filter(
            array_reduce(
                array_map(
                    fn(string $subjectField, $jiraField) => [
                        $jiraField,
                        $attributes[$subjectField] ?? null,
                    ],
                    array_keys(self::FIELDS_MAP_ISSUE),
                    array_values(self::FIELDS_MAP_ISSUE),
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

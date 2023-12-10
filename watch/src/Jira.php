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
        $issueFieldMap = $this->getIssueFieldsMap();
        $this->getClient()->put("issue/$issue->key", [
            'json' => [
                'fields' => array_filter(
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
                ),
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
                    'name' => $type,
                ],
            ],
        ]);
    }

    public function removeLink($linkId): void
    {
        $this->getClient()->delete("issueLink/$linkId");
    }

    public function createIssue(Issue $issue): string
    {
        $json = [
            'fields' => [
                'project' => [
                    'key' => 'OD',
                ],
                'issuetype' => [
                    'id' => '10001',
                ],
                'summary' => $issue->summary,
                $this->getField('duration') => $issue->duration,
                $this->getField('begin') => $issue->begin,
                $this->getField('end') => $issue->end,
            ],
        ];
        if ($issue->started ?? false) {
            $json['transition'] = [
                'id' => '2',
            ];
        }
        if ($issue->completed ?? false) {
            $json['transition'] = [
                'id' => '31',
            ];
        }
        return json_decode($this->getClient()->post("issue", [
            'json' => $json,
        ])->getBody())->key;
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
            "duration" => "customfield_10038",
            "begin" => "customfield_10036",
            "end" => "customfield_10037",
        ];
    }

    private function getField(string $field): string|null
    {
        return $this->getIssueFieldsMap()[$field] ?? null;
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

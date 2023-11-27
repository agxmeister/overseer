<?php

namespace Watch;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;
use Watch\Schedule\Model\Link as ScheduleLink;

readonly class Jira
{
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

    public function createIssue($issue): void
    {
        $fields = [];
        foreach (
            array_filter(
                $issue,
                fn($key) => in_array($key, ['duration', 'begin', 'end']),
                ARRAY_FILTER_USE_KEY
            ) as $key => $value
        ) {
            $fields[$this->fieldsMapping($key)] = $value;
        }
        $json = [
            'fields' => [
                'project' => [
                    'key' => 'OD',
                ],
                'issuetype' => [
                    'id' => '10001',
                ],
                'summary' => $issue['key'],
                ...$fields,
            ],
        ];
        if ($issue['isStarted'] ?? false) {
            $json['transition'] = [
                'id' => '2',
            ];
        }
        if ($issue['isCompleted'] ?? false) {
            $json['transition'] = [
                'id' => '31',
            ];
        }
        $this->getClient()->post("issue", [
            'json' => $json,
        ]);
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
        return new Issue([
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'status' => $status,
            'duration' => (int)$issue->fields->customfield_10038,
            'begin' => $begin,
            'end' => $end,
            'isStarted' => $this->isStarted($status),
            'isCompleted' => $this->isCompleted($status),
            'links' => [
                ...array_values(array_map(
                    fn($link) => new Link(
                        $link->id,
                        $link->outwardIssue->key,
                        $this->getLinkTypeByName($link->type->name),
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
                        $this->getLinkTypeByName($link->type->name),
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

    private function fieldsMapping(string $field): string
    {
        $mapping = [
            "duration" => "customfield_10038",
            "begin" => "customfield_10036",
            "end" => "customfield_10037",
        ];
        return $mapping[$field];
    }

    private function getLinkTypeByName(string $name): string
    {
        return $name === 'Depends' ? ScheduleLink::TYPE_SEQUENCE : ScheduleLink::TYPE_SCHEDULE;
    }

    private function getLinkNameByType(string $type): string
    {
        return $type === ScheduleLink::TYPE_SEQUENCE ? 'Depends' : 'Follows';
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

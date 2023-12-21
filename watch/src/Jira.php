<?php

namespace Watch;

use GuzzleHttp\Exception\GuzzleException;
use Watch\Jira\Client;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;

readonly class Jira
{
    public function __construct(private Client $client, private Config $config)
    {
    }

    /**
     * @param string $issueId
     * @return Issue
     * @throws GuzzleException
     */
    public function getIssue(string $issueId): Issue
    {
        $response = $this->client->http->get("issue/$issueId");
        return $this->getIssueByFields(json_decode($response->getBody()));
    }

    /**
     * @param string $jql
     * @return Issue[]
     * @throws GuzzleException
     */
    public function getIssues(string $jql): array
    {
        $response = $this->client->http->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ]);
        return array_map(fn($issueRaw) => $this->getIssueByFields($issueRaw), json_decode($response->getBody())->issues);
    }

    /**
     * @throws GuzzleException
     */
    public function updateIssue(string $issueId, array $issueAttributes): void
    {
        $this->client->http->put("issue/$issueId", [
            'json' => [
                'fields' => $this->getFieldsByIssueAttributes($issueAttributes),
            ],
        ]);

        if (isset($issueAttributes['status'])) {
            $this->changeIssueStatus($issueId, $issueAttributes['status']);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function createIssue(array $issueAttributes): string
    {
        $issueId = json_decode($this->client->http->post("issue", [
            'json' => [
                'fields' => $this->getFieldsByIssueAttributes($issueAttributes),
            ],
        ])->getBody())->id;

        if (isset($issueAttributes['status'])) {
            $this->changeIssueStatus($issueId, $issueAttributes['status']);
        }

        return $issueId;
    }

    /**
     * @throws GuzzleException
     */
    public function addLink(string $fromIssueId, string $toIssueId, string $linkType): string
    {
        $this->client->http->post("issueLink", [
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
                    'name' => $linkType,
                ],
            ],
        ]);

        return array_reduce(
            json_decode($this->client->http->get("issue/$fromIssueId?fields=issuelinks")->getBody())
                ->fields
                ->issuelinks,
            fn($acc, $link) => (
                ($link->inwardIssue->id === $toIssueId || $link->inwardIssue->key === $toIssueId)
                && $link->type->name === $linkType
            )
                ? $link->id
                : $acc
        );
    }

    /**
     * @throws GuzzleException
     */
    public function removeLink(string $linkId): void
    {
        $this->client->http->delete("issueLink/$linkId");
    }

    /**
     * @throws GuzzleException
     */
    private function changeIssueStatus(string $issueId, string $issueStatus): void
    {
        $issueData = json_decode(
            $this->client->http->get("issue/$issueId?fields=status&expand=transitions")->getBody()
        );
        if ($issueData->fields->status->name === $issueStatus) {
            return;
        }
        $this->client->http->post("issue/$issueId/transitions", [
            'json' => [
                'transition' => [
                    'id' => array_reduce(
                        array_filter(
                            $issueData->transitions,
                            fn($transition) => $transition->to->name === $issueStatus
                        ),
                        fn($acc, $transition) => $transition->id
                    ),
                ],
            ],
        ]);
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
            'project' => $issue->fields->project->id,
            'type' => $issue->fields->issuetype->id,
            'duration' => (int)$issue->fields->customfield_10038,
            'begin' => $begin,
            'end' => $end,
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

    private function getFieldsByIssueAttributes(array $attributes): array
    {
        return
            array_reduce(
                array_filter(
                    array_map(
                        fn(string $subjectAttributeName, $jiraFieldName, $jiraFieldType) => [
                            'name' => $jiraFieldName,
                            'type' => $jiraFieldType,
                            'value' => $attributes[$subjectAttributeName] ?? null,
                        ],
                        array_map(fn($field) => $field->attribute, $this->config->jira->fields),
                        array_map(fn($field) => $field->name, $this->config->jira->fields),
                        array_map(fn($field) => $field->type, $this->config->jira->fields),
                    ),
                    fn($field) => !is_null($field['value']),
                ),
                fn($acc, $field) => [
                    ...$acc,
                    $field['name'] => match ($field['type']) {
                        'reference' => [
                            'id' => $field['value'],
                        ],
                        default => $field['value'],
                    },
                ],
                [],
            );
    }
}

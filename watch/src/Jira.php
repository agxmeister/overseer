<?php

namespace Watch;

use GuzzleHttp\Exception\GuzzleException;
use Watch\Jira\Client;
use Watch\Subject\Model\Issue;
use Watch\Subject\Model\Link;
use Watch\Subject\Model\Subject;

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
     * @param string $issueId
     * @return Link[]
     * @throws GuzzleException
     */
    public function getLinks(string $issueId): array
    {
        $response = $this->client->http->get("issue/$issueId");
        return $this->getLinksByFields(json_decode($response->getBody()));
    }

    /**
     * @param string $jql
     * @return Subject
     * @throws GuzzleException
     */
    public function getSubject(string $jql): Subject
    {
        $issues = json_decode($this->client->http->post('search', [
            'json' => [
                'jql' => $jql,
            ],
        ])->getBody())->issues;
        return new Subject(
            array_map(
                fn($issue) => $this->getIssueByFields($issue),
                $issues,
            ),
            array_reduce(
                array_map(
                    fn($issue) => $this->getLinksByFields($issue),
                    $issues,
                ),
                fn($acc, $links) => [...$acc, ...$links],
                [],
            )
        );
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
        $milestone = array_reduce(
            array_map(
                fn($version) => $version->name,
                $issue->fields->fixVersions ?? [],
            ),
            fn($acc, $versionName) => $versionName,
        );
        return new Issue(...[
            'id' => $issue->id,
            'key' => $issue->key,
            'summary' => $issue->fields->summary,
            'status' => $status,
            'milestone' => $milestone,
            'project' => $issue->fields->project->key,
            'type' => $issue->fields->issuetype->name,
            'duration' => (int)$issue->fields->customfield_10038,
            'begin' => $begin,
            'end' => $end,
        ]);
    }

    private function getLinksByFields($issue): array
    {
        return [
            ...array_values(array_map(
                fn($link) => new Link(
                    $link->id,
                    $link->outwardIssue->key,
                    $issue->key,
                    $link->type->name,
                ),
                array_filter(
                    $issue->fields->issuelinks,
                    fn($link) => isset($link->outwardIssue)
                )
            )),
            ...array_values(array_map(
                fn($link) => new Link(
                    $link->id,
                    $issue->key,
                    $link->inwardIssue->key,
                    $link->type->name,
                ),
                array_filter(
                    $issue->fields->issuelinks,
                    fn($link) => isset($link->inwardIssue)
                )
            )),
        ];
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
                        array_map(fn($field) => $field->type ?? 'scalar', $this->config->jira->fields),
                    ),
                    fn($field) => !is_null($field['value']),
                ),
                fn($acc, $field) => [
                    ...$acc,
                    $field['name'] => match ($field['type']) {
                        'ref/key' => [
                            'key' => $field['value'],
                        ],
                        'ref/name' => [
                            'name' => $field['value'],
                        ],
                        'ref/id' => [
                            'id' => $field['value'],
                        ],
                        default => $field['value'],
                    },
                ],
                [],
            );
    }
}

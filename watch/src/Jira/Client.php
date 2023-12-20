<?php

namespace Watch\Jira;

use GuzzleHttp\Client as HttpClient;

readonly class Client
{
    public HttpClient $http;

    public function __construct(private string $apiUrl, private string $apiUsername, private string $apiToken)
    {
        $this->http = new HttpClient([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiUsername . ':' . $this->apiToken),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}

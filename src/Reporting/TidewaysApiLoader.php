<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

use GuzzleHttp;

class TidewaysApiLoader
{
    private const BASE_URI = "https://app.tideways.io/apps/api/";

    private string $project;
    private string $apiToken;
    private GuzzleHttp\Client $client;

    public function __construct(string $project, string $apiToken)
    {
        $this->project = $project;
        $this->apiToken = $apiToken;

        $headers = ['Authorization' => sprintf('Bearer %s', $this->apiToken)];
        $this->client = new GuzzleHttp\Client(['base_uri' => self::BASE_URI . $this->project . '/', 'headers' => $headers]);
    }

    public function fetchOverallPerformanceData(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $url = $this->getPerformanceApiUrl('performance', $end, $start);
        $response = $this->client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

        return $data['application']['by_time'];
    }

    public function fetchTransactionPerformanceData(
        string             $transactionName,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): array
    {
        $url = $this->getPerformanceApiUrl('transaction-by-name/' . $transactionName, $start, $end);
        $response = $this->client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

        return $data['transaction']['by_time'];
    }

    private function getPerformanceApiUrl(string $path, \DateTimeImmutable $end, \DateTimeImmutable $start): string
    {
        $until = $end->format("Y-m-d H:i");
        $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60 - 1;

        return sprintf("%s?ts=%s&m=%d", $path, $until, max(60, $duration));
    }
}

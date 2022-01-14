<?php

namespace Tideways\Shopware6Loadtesting\Reporting;

use \GuzzleHttp;

class TidewaysApiLoader
{
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function fetchTidewaysPerformanceData( \DateTimeImmutable $start, \DateTimeImmutable $end ): array
    {
        $baseUrl = "https://app.tideways.io/apps/api/";
        $client = new GuzzleHttp\Client(['base_uri' => $baseUrl]);
        $url = sprintf(
            "demos-tideways/Shopware6/performance?ts=%s&m=%d",
            $end->format("Y-m-d H:i"),
            $end->diff($start)->i + 1
        );

        $headers = ['Authorization' => sprintf('Bearer %s', $this->apiToken)];
        $response = $client->request('GET', $url, ['headers' => $headers]);

        $data = json_decode($response->getBody(), true);

        return $data['application']['by_time'];
    }

}

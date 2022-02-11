<?php
/**
 * SWBench
 * Copyright (C) 2022 Tideways GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tideways\Shopware6Benchmarking\Reporting;

use GuzzleHttp;
use Tideways\Shopware6Benchmarking\TidewaysConfiguration;

class TidewaysApiLoader implements PerformanceLoader
{
    private const BASE_URI = "https://app.tideways.io/apps/api/";

    private string $project;
    private string $apiToken;
    private string $environment;
    private GuzzleHttp\Client $client;

    public function __construct(TidewaysConfiguration $configuration)
    {
        $this->project = $configuration->project;
        $this->apiToken = $configuration->apiToken;
        $this->environment = $configuration->environment;

        $headers = ['Authorization' => sprintf('Bearer %s', $this->apiToken)];
        $this->client = new GuzzleHttp\Client(['base_uri' => self::BASE_URI . $this->project . '/', 'headers' => $headers]);
    }

    public function fetchOverallPerformanceData(\DateTimeImmutable $start, \DateTimeImmutable $end): TidewaysStats
    {
        $url = $this->getPerformanceApiUrl('performance', $end, $start);
        $response = $this->client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

        return new TidewaysStats(
            byTime: $data['application']['by_time'],
            responseTime: $data['application']['total']['response_time'],
            requests: $data['application']['total']['requests'],
            errors: $data['application']['total']['error_rate'] ?? 0.0,
        );
    }

    public function fetchTransactionPerformanceData(
        string             $transactionName,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): TidewaysStats
    {
        $url = $this->getPerformanceApiUrl('transaction-by-name/' . $transactionName, $end, $start);
        $response = $this->client->request('GET', $url);
        $data = json_decode($response->getBody(), true);

        return new TidewaysStats(
            byTime: $data['transaction']['by_time'],
            responseTime: $data['transaction']['total']['response_time'],
            requests: $data['transaction']['total']['requests'],
            errors: $data['transaction']['total']['error_rate'] ?? 0.0,
        );
    }

    private function getPerformanceApiUrl(string $path, \DateTimeImmutable $end, \DateTimeImmutable $start): string
    {
        $until = $end->format("Y-m-d H:i");
        $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;

        return sprintf("%s?ts=%s&m=%d&env=%s", $path, $until, $duration - 1, $this->environment);
    }

    /** @return TidewaysTrace[] */
    public function fetchTraces(
        string             $transactionName,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ) : iterable
    {
        $query = [
            'transaction_name' => $transactionName,
            'min_date' => $start->format('Y-m-d H:i'),
            'max_date' => $end->format('Y-m-d H:i'),
            'sort_by' => 'response_time',
            'sort_order' => 'DESC',
            'limit' => 1,
            'env' => $this->environment,
        ];
        try {
            $response = $this->client->request('GET', 'traces?' . http_build_query($query));
        } catch (GuzzleHttp\Exception\RequestException $e) {
            return [];
        }

        return iterator_to_array(TidewaysTrace::createListFromApiPayload(
            json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR)
        ));
    }
}

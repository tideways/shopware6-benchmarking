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

class TidewaysTrace
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $date,
        public string $url,
        public int $responseTimeMs,
        public float $memoryMb,
        public string $transactionName,
        public string $htmlUrl,
        public bool $hasCallgraph = false,
        public string $httpMethod = '',
        public int $httpStatusCode = 200,
    ) {}

    /**
     * @return \Generator<TidewaysTrace>
     */
    public static function createListFromApiPayload(array $payload) : \Generator
    {
        $payload['traces'] = $payload['traces'] ?? [];

        foreach ($payload['traces'] as $tracePayload) {
            yield new TidewaysTrace(
                id: $tracePayload['id'],
                date: \DateTimeImmutable::createFromFormat('Y-m-d H:i', $tracePayload['date']),
                url: $tracePayload['http']['url'] ?? '',
                htmlUrl: $tracePayload['_links']['html_url'],
                responseTimeMs: $tracePayload['response_time_ms'],
                memoryMb: round($tracePayload['memory_kb'] / 1024, 1),
                transactionName: $tracePayload['transaction_name'],
                httpMethod: $tracePayload['http']['method'] ?? '',
                httpStatusCode: $tracePayload['http']['status_code'] ?? 0,
            );
        }
    }
}
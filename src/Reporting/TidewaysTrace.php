<?php

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
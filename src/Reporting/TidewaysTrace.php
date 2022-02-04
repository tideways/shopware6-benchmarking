<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class TidewaysTrace
{
    public function __construct(
        public string $id,
        public \DateTimeImmutable $date,
        public string $url,
        public int $responseTimeMs,
        public int $memoryKb,
        public string $transactionName,
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
                responseTimeMs: $tracePayload['response_time_ms'],
                memoryKb: $tracePayload['memory_kb'],
                transactionName: $tracePayload['transaction_name'],
                httpMethod: $tracePayload['http']['method'] ?? '',
                httpStatusCode: $tracePayload['http']['status_code'] ?? 0,
            );
        }
    }
}
<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

interface PerformanceLoader
{
    public function fetchOverallPerformanceData(\DateTimeImmutable $start, \DateTimeImmutable $end): TidewaysStats;
    public function fetchTransactionPerformanceData(
        string             $transactionName,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): TidewaysStats;

    /** @return TidewaysTrace[] */
    public function fetchTraces(
        string             $transactionName,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ) : iterable;
}
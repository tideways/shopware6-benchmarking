<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class EmptyPerformanceLoader implements PerformanceLoader
{
    public function fetchOverallPerformanceData(\DateTimeImmutable $start, \DateTimeImmutable $end): TidewaysStats
    {
        return new TidewaysStats(byTime: $this->createEmptyTimes($start, $end));
    }

    public function fetchTransactionPerformanceData(string $transactionName, \DateTimeImmutable $start, \DateTimeImmutable $end): TidewaysStats
    {
        return new TidewaysStats(byTime: $this->createEmptyTimes($start, $end));
    }

    public function fetchTraces(string $transactionName, \DateTimeImmutable $start, \DateTimeImmutable $end): iterable
    {
        return new \ArrayIterator([]);
    }

    private function createEmptyTimes(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $period = new \DatePeriod($start, new \DateInterval('PT1M'), $end);

        $byTime = [];

        foreach ($period as $everyMinute) {
            $byTime[$everyMinute->format('Y-m-d H:i')] = ['requests' => 0, 'percentile_95p' => 0, 'errors' => 0];
        }

        return $byTime;
    }
}
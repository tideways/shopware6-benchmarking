<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class LocustStats
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public array              $pageByTime = [],
        public array              $pageSummary = [],
    ) {}

    public function getTotalRequests() : int
    {
        return array_sum(array_map(fn (array $row) => $row['Request Count'], $this->pageSummary));
    }
}
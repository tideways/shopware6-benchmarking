<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class LocustStats
{
    public function __construct(
        public ?\DateTimeImmutable $startDate = null,
        public ?\DateTimeImmutable $endDate = null,
        /** @var array<string, array<string, HdrHistogram>> */
        public array               $pageByTime = [],
        /** @var array<string, HdrHistogram> */
        public array               $pageSummary = [],
    ) {}

    public function getTotalRequests() : int
    {
        return array_sum(array_map(fn (array $row) => $row['Request Count'], $this->pageSummary));
    }
}
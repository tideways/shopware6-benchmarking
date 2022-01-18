<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class LocustStats
{
    public function __construct(
        public \DateTimeImmutable $startDate,
        public \DateTimeImmutable $endDate,
        public array $pagePercentiles = [],
    ) {}
}
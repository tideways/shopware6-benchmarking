<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class TidewaysStats
{
    public function __construct(
        public array $byTime = [],
        public int $responseTime = 0,
        public int $requests = 0,
    ) {}
}
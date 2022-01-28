<?php

namespace Tideways\Shopware6Benchmarking;

class ScenarioConfiguration
{
    public function __construct(
        public string $title,
        public string $duration,
        public string $host,
        public int $concurrentThreads = 10,
        public int $userSpawnRate = 1,
        public int $recurringUserRate = 50,
        public int $filtererMinFilters = 3,
        public int $filtererMaxFilters = 5,
        public int $filtererVisitProductRatio = 10,
        public int $maxPaginationSurfing = 3,
    ) {}
}

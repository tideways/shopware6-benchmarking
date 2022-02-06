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
        /** Percentage of users that are acting as guests. */
        public int $browsingGuestRatio = 90,
        /** The percentage of non-guest users that create a new account instead of logging into an existing. */
        public int $browsingAccountsNewRatio = 0,
        /** During checkout, percentage of not logged in users that stay a guest or create a new account */
        public int $checkoutGuestRatio = 50,
        public int $checkoutAccountsNewRatio = 50,
        public int $filtererMinFilters = 3,
        public int $filtererMaxFilters = 5,
        public int $filtererVisitProductRatio = 10,
        public int $maxPaginationSurfing = 3,
    ) {}
}

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

        /** Conversion Ratio, Percentage of users that will end their visit with a checkout */
        public int $conversionRatio = 5,

        /** Ratio of users that add a product to the cart and abandon it */
        public int $cartAbandonmentRatio = 5,

        public int $filtererMinFilters = 3,
        public int $filtererMaxFilters = 5,
        public int $filtererVisitProductRatio = 10,
        public int $maxPaginationSurfing = 3,
    ) {}
}

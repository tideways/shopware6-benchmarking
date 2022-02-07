<?php
/**
 * SWBench
 * Copyright (C) 2022 Tideways GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tideways\Shopware6Benchmarking;

class ScenarioConfiguration
{
    public function __construct(
        public string $title,
        public string $duration,
        public string $host,
        public string $description = '',
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

    public function getBrowsingLoggedInRatio() : float
    {
        return (100 - $this->browsingGuestRatio) * (100 - $this->browsingAccountsNewRatio) / 100;
    }

    public function getBrowsingNewAccountRatio() : float
    {
        return (100 - $this->browsingGuestRatio) * ($this->browsingAccountsNewRatio) / 100;
    }

    public function getCheckoutLoggedInRatio() : float
    {
        return (100 - $this->checkoutGuestRatio) * (100 - $this->checkoutAccountsNewRatio) / 100;
    }

    public function getCheckoutNewAccountRatio() : float
    {
        return (100 - $this->checkoutGuestRatio) * ($this->checkoutAccountsNewRatio) / 100;
    }
}

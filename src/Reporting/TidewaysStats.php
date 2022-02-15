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

namespace Tideways\Shopware6Benchmarking\Reporting;

class TidewaysStats
{
    public function __construct(
        public array $byTime = [],
        public int $responseTime = 0,
        public int $medianResponseTime = 0,
        public int $requests = 0,
        public float $errors = 0,
    ) {}

    public static function createFromLocustStats(LocustStats $locustStats, string $slug): self
    {
        $byTime = [];

        if (!isset($locustStats->pageByTime[$slug]) || !isset($locustStats->pageSummary[$slug])) {
            return new TidewaysStats();
        }

        foreach ($locustStats->pageByTime[$slug] as $time => $histogram) {
            $byTime[$time] = [
                'percentile_95p' => $histogram ? $histogram->get95PercentileResponseTime() : 0,
                'requests' => $histogram ? $histogram->getRequestCount() : 0,
                'errors' => 0,
            ];
        }

        return new TidewaysStats(
            byTime: $byTime,
            requests: $locustStats->pageSummary[$slug]->getRequestCount(),
            responseTime: $locustStats->pageSummary[$slug]->get95PercentileResponseTime(),
            medianResponseTime: $locustStats->pageSummary[$slug]->getMedianResponseTime(),
            errors: 0,
        );
    }
}
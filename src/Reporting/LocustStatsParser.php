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

class LocustStatsParser
{
    public function parse(string $requestsFilePath) : LocustStats
    {
        $handle = fopen($requestsFilePath, "r");

        $minTimestamp = null;
        $maxTimestamp = null;
        $headers = fgetcsv($handle);

        $operationNames = ['order', 'listing-page', 'product-detail-page', 'homepage', 'search', 'add-to-cart', 'cart-page', 'register'];

        $operations = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);
            $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['timeStamp']);

            if (!$date) {
                continue;
            }

            $time = $date->format('Y-m-d H:i');
            $duration = (int) $row['elapsed'];
            $page = $row['label'];

            if ($minTimestamp === null) {
                $minTimestamp = $date;
            }
            $maxTimestamp = $date;

            if (!isset($operations[$page])) {
                $operations[$page] = ['summary' => new HdrHistogram(), 'byTime' => []];
            }

            if (!isset($operations['overall'])) {
                $operations['overall'] = ['summary' => new HdrHistogram(), 'byTime' => []];
            }

            $operations[$page]['summary']->record($duration);
            $operations['overall']['summary']->record($duration);

            if (!isset($operations[$page]['byTime'][$time])) {
                $operations[$page]['byTime'][$time] = $this->createHistogram();
            }

            if (!isset($operations['overall']['byTime'][$time])) {
                $operations['overall']['byTime'][$time] = $this->createHistogram();
            }

            $operations[$page]['byTime'][$time]->record($duration);
            $operations['overall']['byTime'][$time]->record($duration);
        }

        $maxTimestamp = $maxTimestamp->modify('+1 minute');

        return $this->createLocustStats($minTimestamp, $maxTimestamp, $operations);
    }

    private function createLocustStats(
        \DateTimeImmutable $minTimestamp,
        \DateTimeImmutable $maxTimestamp,
        array $operations
    ): LocustStats
    {
        $stats = new LocustStats(
            startDate: $minTimestamp,
            endDate: $maxTimestamp,
            pageByTime: [],
            pageSummary: [],
        );

        $period = new \DatePeriod($stats->startDate, new \DateInterval('PT1M'), $stats->endDate);

        foreach ($operations as $operation => $operationStats) {
            $stats->pageSummary[$operation] = $operationStats['summary'];

            foreach ($operationStats['byTime'] as $time => $timeStats) {
                $stats->pageByTime[$operation][$time] = $timeStats;
            }

            foreach ($period as $everyMinute) {
                if (isset($stats->pageByTime[$operation][$everyMinute->format('Y-m-d H:i')])) {
                    continue;
                }

                $stats->pageByTime[$operation][$everyMinute->format('Y-m-d H:i')] = $this->createHistogram();
            }
            ksort($stats->pageByTime[$operation]);
        }

        return $stats;
    }

    private function createHistogram() : Histogram
    {
        if (extension_loaded('hdrhistogram')) {
            return new HdrHistogram();
        }
        return new SimpleHistogram();
    }
}

<?php

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
                $operations[$page]['byTime'][$time] = new HdrHistogram();
            }

            if (!isset($operations['overall']['byTime'][$time])) {
                $operations['overall']['byTime'][$time] = new HdrHistogram();
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

                $stats->pageByTime[$operation][$everyMinute->format('Y-m-d H:i')] = new HdrHistogram();
            }
        }

        return $stats;
    }
}

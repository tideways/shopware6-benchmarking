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

        $stats = new LocustStats(
            startDate: $minTimestamp,
            endDate: $maxTimestamp,
            pageByTime: [],
            pageSummary: [],
        );

        $period = new \DatePeriod($minTimestamp, new \DateInterval('PT1M'), $maxTimestamp);

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

    public function parseLocustStats(string $statsFilePath, string $statsHistoryFilePath): LocustStats
    {
        $stats = $this->parseStatsHistory($statsHistoryFilePath);
        $stats->pageSummary = $this->parseStats($statsFilePath);
        return $stats;
    }

    private function parseStats(string $statsFilePath)
    {
        $handle = fopen($statsFilePath, "r");

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not open stats file at %s', $statsFilePath));
        }

        // Skip headline
        $headers = fgetcsv($handle);
        $stats = [];

        while (($data = fgetcsv($handle)) !== false) {
            $row = array_combine($headers, $data);
            $stats[$row['Name']] = $row;
        }

        return $stats;
    }

    private function parseStatsHistory(string $statsHistoryFilePath): LocustStats
    {
        $stats = [];
        $handle = fopen($statsHistoryFilePath, "r");

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not open stats file at %s', $statsHistoryFilePath));
        }

        // Skip headline
        fgetcsv($handle);

        $minTimestamp = PHP_INT_MAX;
        $maxTimestamp = 0;

        while (($data = fgetcsv($handle)) !== false) {
            // The stats CSV sometimes contains null bytes around the timestamp 🤷
            $timestamp = intval(trim($data[0]));
            $name = $data[3];

            $minTimestamp = min($minTimestamp, $timestamp);
            $maxTimestamp = max($maxTimestamp, $timestamp);

            if (!isset($stats[$name])) {
                $stats[$name] = [];
            }

            // Use data from 95-percentile
            $stats[$name][$timestamp] = $data[11];
        }

        fclose($handle);

        return new LocustStats(
            pageByTime: $stats,
            startDate: new \DateTimeImmutable('@' . $minTimestamp, new \DateTimeZone('UTC')),
            endDate: new \DateTimeImmutable('@' . $maxTimestamp, new \DateTimeZone('UTC')),
        );
    }
}

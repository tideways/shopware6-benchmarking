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
                $operations[$page] = ['summary' => hdr_init(1, 60000, 2), 'byTime' => []];
            }

            if (!isset($operations['overall'])) {
                $operations['overall'] = ['summary' => hdr_init(1, 60000, 2), 'byTime' => []];
            }

            hdr_record_value($operations[$page]['summary'], $duration);
            hdr_record_value($operations['overall']['summary'], $duration);

            if (!isset($operations[$page]['byTime'][$time])) {
                $operations[$page]['byTime'][$time] = hdr_init(1, 60000, 2);
            }

            if (!isset($operations['overall']['byTime'][$time])) {
                $operations['overall']['byTime'][$time] = hdr_init(1, 60000, 2);
            }

            hdr_record_value($operations[$page]['byTime'][$time], $duration);
            hdr_record_value($operations['overall']['byTime'][$time], $duration);
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
            $stats->pageSummary[$operation] = [
                'response_time_median' => hdr_value_at_percentile($operationStats['summary'], 0.50),
                'response_time_95p' => hdr_value_at_percentile($operationStats['summary'], 0.95),
                'requests' => hdr_total_count($operationStats['summary']),
            ];

            foreach ($period as $everyMinute) {
                $stats->pageByTime[$operation][$everyMinute->format('Y-m-d H:i')] = [
                    'response_time_median' => null,
                    'response_time_95p' => null,
                    'requests' => 0,
                ];
            }

            foreach ($operationStats['byTime'] as $time => $timeStats) {
                $stats->pageByTime[$operation][$time] = [
                    'response_time_median' => hdr_value_at_percentile($timeStats, 0.50),
                    'response_time_95p' => hdr_value_at_percentile($timeStats, 0.95),
                    'requests' => hdr_total_count($timeStats),
                ];
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

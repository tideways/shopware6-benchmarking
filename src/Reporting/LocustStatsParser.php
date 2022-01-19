<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class LocustStatsParser
{
    public function parseLocustStats(string $statsFilePath): LocustStats
    {
        $stats = [];
        $handle = fopen($statsFilePath, "r");

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not open stats file at %s', $statsFilePath));
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
            pagePercentiles: $stats,
            startDate: new \DateTimeImmutable('@' . $minTimestamp, new \DateTimeZone('UTC')),
            endDate: new \DateTimeImmutable('@' . $maxTimestamp, new \DateTimeZone('UTC')),
        );
    }
}

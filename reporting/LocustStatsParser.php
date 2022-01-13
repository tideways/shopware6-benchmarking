<?php

namespace Tideways\Shopware6Loadtesting\Reporting;

class LocustStatsParser
{
    public function parseLocustStats(string $statsFilePath): array
    {
        $stats = [];
        $handle = fopen($statsFilePath, "r");

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Could not open stats file at %s', $statsFilePath));
        }

        // Skip headline
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            // The stats CSV sometimes contains null bytes around the timestamp 🤷
            $timestamp = intval(trim($data[0]));
            $name = $data[3];

            if (!isset($stats[$name])) {
                $stats[$name] = [];
            }

            // Use data from 95-percentile
            $stats[$name][$timestamp] = $data[11];
        }

        fclose($handle);

        return $stats;
    }
}

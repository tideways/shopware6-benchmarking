<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class HdrHistogram
{
    private $hdr;
    private int $count = 0;

    public function __construct()
    {
        $this->hdr = hdr_init(1, 60000, 2);
    }

    public function record(int $value) : void
    {
        hdr_record_value($this->hdr, $value);
        $this->count++;
    }

    public function getMedianResponseTime() : int
    {
        return hdr_value_at_percentile($this->hdr, 50);
    }

    public function get95PercentileResponseTime() : int
    {
        return hdr_value_at_percentile($this->hdr, 95);
    }

    public function getRequestCount() : int
    {
        return $this->count;
    }

    public function exportAsBuckets() : array
    {
        $max = hdr_max($this->hdr);

        $step = $this->estimateOptimalStep($max);

        $buckets = [];

        $iter = hdr_iter_init($this->hdr);

        while ($row = hdr_iter_next($iter)) {
            if ($row['value'] > $max) {
                break;
            }
            $start = $row['value'] - ($row['value'] % $step);
            $label = $start . '-' . ($start+$step);
            if (!isset($buckets[$label])) {
                $buckets[$label] = 0;
            }
            $buckets[$label] += $row['count_at_index'];
        }

        return $buckets;
    }

    private function estimateOptimalStep(int $max) : float
    {
        return max(pow(2, ceil(log($max)) - 2), 2);
    }
}
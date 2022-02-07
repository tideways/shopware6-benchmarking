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

        $buckets = ['Excellent' => 0, 'Good' => 0, 'Acceptable' => 0, 'Degraded' => 0, 'Unacceptable' => 0];

        $iter = hdr_iter_init($this->hdr);

        while ($row = hdr_iter_next($iter)) {
            if ($row['value'] > $max) {
                break;
            }

            if ($row['value'] < 512) {
                $label = 'Excellent';
            } else if ($row['value'] < 1024) {
                $label = 'Good';
            } else if ($row['value'] < 2048) {
                $label = 'Acceptable';
            } else if ($row['value'] < 4096) {
                $label = 'Degraded';
            } else {
                $label = 'Unacceptable';
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
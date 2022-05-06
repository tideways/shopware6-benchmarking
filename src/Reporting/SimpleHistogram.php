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

class SimpleHistogram implements Histogram
{
    private array $values = [];
    private bool $sorted = false;
    
    public function record(int $value): void
    {
        $this->values[] = $value;
        $this->sorted = false;
    }

    public function getMedianResponseTime(): int
    {
        $this->sort();
        
        if ($this->getRequestCount() === 0) {
            return 0;
        }
        
        $index = floor($this->getRequestCount() / 2);
        
        return $this->values[$index];
    }

    public function get95PercentileResponseTime(): int
    {
        $this->sort();

        if ($this->getRequestCount() === 0) {
            return 0;
        }

        $index = floor($this->getRequestCount() * 0.95);

        return $this->values[$index];
    }

    public function getRequestCount(): int
    {
        return count($this->values);
    }

    public function exportAsBuckets(): array
    {
        $buckets = ['Excellent' => 0, 'Good' => 0, 'Acceptable' => 0, 'Degraded' => 0, 'Unacceptable' => 0];

        foreach ($this->values as $value) {
            if ($value < 512) {
                $label = 'Excellent';
            } else if ($value < 1024) {
                $label = 'Good';
            } else if ($value < 2048) {
                $label = 'Acceptable';
            } else if ($value < 4096) {
                $label = 'Degraded';
            } else {
                $label = 'Unacceptable';
            }

            $buckets[$label]++;
        }

        return $buckets;
    }
    
    private function sort() : void
    {
        if ($this->sorted === true) {
            return;
        }
        sort($this->values);
        $this->sorted = true;
    }
}

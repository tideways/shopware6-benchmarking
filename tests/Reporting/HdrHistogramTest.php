<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

use PHPUnit\Framework\TestCase;

class HdrHistogramTest extends TestCase
{
    public function testMetrics() : void
    {
        $histogram = new HdrHistogram();

        for ($i = 1; $i <= 100; $i++) {
            $histogram->record($i);
        }

        $this->assertEquals(50, $histogram->getMedianResponseTime());
        $this->assertEquals(95, $histogram->get95PercentileResponseTime());
        $this->assertEquals(100, $histogram->getRequestCount());
    }

    public function testExportAsBucketsS() : void
    {
        $histogram = new HdrHistogram();

        for ($i = 1; $i <= 100; $i++) {
            $histogram->record($i);
        }

        $this->assertEquals([
            '0-8' => 7,
            '8-16' => 8,
            '16-24' => 8,
            '24-32' => 8,
            '32-40' => 8,
            '40-48' => 8,
            '48-56' => 8,
            '56-64' => 8,
            '64-72' => 8,
            '72-80' => 8,
            '80-88' => 8,
            '88-96' => 8,
            '96-104' => 5,
        ], $histogram->exportAsBuckets());
    }
}
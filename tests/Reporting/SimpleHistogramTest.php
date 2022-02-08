<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

use PHPUnit\Framework\TestCase;

class SimpleHistogramTest extends TestCase
{
    public function testMetrics() : void
    {
        $histogram = new SimpleHistogram();

        for ($i = 1; $i <= 100; $i++) {
            $histogram->record($i);
        }

        $this->assertEquals(51, $histogram->getMedianResponseTime());
        $this->assertEquals(96, $histogram->get95PercentileResponseTime());
        $this->assertEquals(100, $histogram->getRequestCount());
    }

    public function testExportAsBuckets() : void
    {
        $histogram = new SimpleHistogram();

        for ($i = 1; $i <= 4000; $i++) {
            $histogram->record($i);
        }

        $this->assertEquals([
            'Excellent' => 511,
            'Good' => 512,
            'Acceptable' => 1024,
            'Degraded' => 1953,
            'Unacceptable' => 0,
        ], $histogram->exportAsBuckets());
    }
}
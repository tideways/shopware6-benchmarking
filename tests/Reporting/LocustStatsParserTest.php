<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

use PHPUnit\Framework\TestCase;

class LocustStatsParserTest extends TestCase
{
    public function testParseLoadTesting1Fixture() : void
    {
        $parser = new LocustStatsParser();
        $stats = $parser->parse(__DIR__ . '/../fixtures/loadtesting1_requests.csv');

        $this->assertEquals(1447, $stats->pageSummary['order']->get95PercentileResponseTime());
        $this->assertEquals(1319, $stats->pageSummary['order']->getMedianResponseTime());
        $this->assertEquals(4, $stats->pageSummary['order']->getRequestCount());

        $this->assertEquals(871, $stats->pageSummary['overall']->get95PercentileResponseTime());
        $this->assertEquals(235, $stats->pageSummary['overall']->getMedianResponseTime());
        $this->assertEquals(469, $stats->pageSummary['overall']->getRequestCount());

        $this->assertCount(2, $stats->pageByTime['overall']);

        $this->assertEquals(883, $stats->pageByTime['overall']['2022-01-28 14:38']->get95PercentileResponseTime());
        $this->assertEquals(234, $stats->pageByTime['overall']['2022-01-28 14:38']->getMedianResponseTime());
        $this->assertEquals(409, $stats->pageByTime['overall']['2022-01-28 14:38']->getRequestCount());

        $this->assertEquals(835, $stats->pageByTime['overall']['2022-01-28 14:39']->get95PercentileResponseTime());
        $this->assertEquals(263, $stats->pageByTime['overall']['2022-01-28 14:39']->getMedianResponseTime());
        $this->assertEquals(60, $stats->pageByTime['overall']['2022-01-28 14:39']->getRequestCount());
    }
}
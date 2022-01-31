<?php

namespace Reporting;

use PHPUnit\Framework\TestCase;
use Tideways\Shopware6Benchmarking\Reporting\LocustStatsParser;

class LocustStatsParserTest extends TestCase
{
    public function testParseLoadTesting1Fixture() : void
    {
        $parser = new LocustStatsParser();
        $stats = $parser->parse(__DIR__ . '/../fixtures/loadtesting1_requests.csv');

        $this->assertEquals(1191, $stats->pageSummary['order']['response_time_95p']);
        $this->assertEquals(1191, $stats->pageSummary['order']['response_time_median']);
        $this->assertEquals(4, $stats->pageSummary['order']['requests']);

        $this->assertEquals(72, $stats->pageSummary['overall']['response_time_95p']);
        $this->assertEquals(71, $stats->pageSummary['overall']['response_time_median']);
        $this->assertEquals(469, $stats->pageSummary['overall']['requests']);

        $this->assertCount(2, $stats->pageByTime['overall']);

        $this->assertEquals(72, $stats->pageByTime['overall']['2022-01-28 15:38']['response_time_95p']);
        $this->assertEquals(71, $stats->pageByTime['overall']['2022-01-28 15:38']['response_time_median']);
        $this->assertEquals(409, $stats->pageByTime['overall']['2022-01-28 15:38']['requests']);

        $this->assertEquals(97, $stats->pageByTime['overall']['2022-01-28 15:39']['response_time_95p']);
        $this->assertEquals(97, $stats->pageByTime['overall']['2022-01-28 15:39']['response_time_median']);
        $this->assertEquals(60, $stats->pageByTime['overall']['2022-01-28 15:39']['requests']);
    }
}
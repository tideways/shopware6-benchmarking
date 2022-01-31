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
    }
}
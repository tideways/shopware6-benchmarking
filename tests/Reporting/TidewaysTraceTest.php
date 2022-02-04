<?php

namespace Reporting;

use PHPUnit\Framework\TestCase;
use Tideways\Shopware6Benchmarking\Reporting\TidewaysTrace;

class TidewaysTraceTest extends TestCase
{
    public function testCreateFromApiPAyload()
    {
        $payload = json_decode(file_get_contents(__DIR__ . '/../fixtures/tideways_traces.json'), true, flags: JSON_THROW_ON_ERROR);
        $traces = iterator_to_array(TidewaysTrace::createListFromApiPayload($payload));

        $this->assertCount(2, $traces);
        $this->assertContainsOnly(TidewaysTrace::class, $traces);

        $this->assertEquals("chp_vn4B2WPJVWcL29bJ", $traces[0]->id);
        $this->assertEquals('2022-02-03 07:32', $traces[0]->date->format('Y-m-d H:i'));
        $this->assertEquals('Shopware\Storefront\Controller\ProductController::index', $traces[0]->transactionName);
        $this->assertEquals(41, $traces[0]->responseTimeMs);
        $this->assertEquals('shopware-demo.tideways.io/Aerodynamic-Cotton-Dino-Diamonds/4c07616867334cf4ac04bd8ac61f9956', $traces[0]->url);

        $this->assertEquals("Y5_vn4BsSpQoyXl3Ppy", $traces[1]->id);
        $this->assertEquals('2022-02-03 07:32', $traces[1]->date->format('Y-m-d H:i'));
        $this->assertEquals('Shopware\Storefront\Controller\CheckoutController::info', $traces[1]->transactionName);
        $this->assertEquals(87, $traces[1]->responseTimeMs);
        $this->assertEquals('shopware-demo.tideways.io/widgets/checkout/info', $traces[1]->url);
    }
}
<?php

namespace Tideways\Shopware6Benchmarking;

use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testLoadFromTestfile(): void
    {
        $config = Configuration::fromFile(__DIR__ . '/fixtures/test_scenario.json');

        $this->assertEquals('test_scenario', $config->getName());
        $this->assertEquals('Small Shop with 10.000 products and 300 categories', $config->scenario->title);
        $this->assertEquals('6.4 Community Edition', $config->shopware->version);
        $this->assertEquals('foobar', $config->tideways->apiToken);
    }
}
<?php

namespace Tideways\Shopware6Benchmarking;

class ShopwareConfiguration
{
    public function __construct(
        public string $version = '',
        public string $phpVersion = '',
        public string $serverHardware = '',
    ) {}
}
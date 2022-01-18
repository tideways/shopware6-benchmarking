<?php

namespace Tideways\Shopware6Benchmarking;

class ShopwareConfiguration
{
    public function __construct(
        public string $version,
        public string $phpVersion,
        public string $serverHardware,
        public string $httpCacheLifetime,
        public string $cacheBackend,
        public string $productSearchBackend,
        public string $backgroundQueue,
        public int $storefronts,
        public int $exports,
        public array $plugins = [],
    ) {}
}
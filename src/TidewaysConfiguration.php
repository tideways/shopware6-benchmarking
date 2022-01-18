<?php

namespace Tideways\Shopware6Benchmarking;

class TidewaysConfiguration
{
    public function __construct(
        public string $apiToken,
        public string $project,
    ) {}
}
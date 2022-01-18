<?php

namespace Tideways\Shopware6Benchmarking;

class ScenarioConfiguration
{
    public function __construct(
        public string $title,
        public string $duration,
        public string $host,
    ) {}
}
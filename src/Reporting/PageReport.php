<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class PageReport
{
    public function __construct(
        public string $slug,
        public string $label,
        public string $transactionName = '',
        public ?TidewaysStats $tideways = null,
        /** Rework TidewaysStats to another name or introduce new */
        public ?TidewaysStats $locust = null,
    ) {}
}
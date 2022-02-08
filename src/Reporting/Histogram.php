<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

interface Histogram
{
    public function record(int $value) : void;
    public function getMedianResponseTime() : int;
    public function get95PercentileResponseTime() : int;
    public function getRequestCount() : int;
    public function exportAsBuckets() : array;
}
<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

use ezcGraphArrayDataSet;
use ezcGraphBarChart;
use ezcGraphGdDriver;

class HistogramGenerator
{
    public function __construct(private string $dataDir) {}

    public function generateChartsFromLocustStats(LocustStats $locustStats): void
    {
        foreach ($locustStats->pageSummary as $page => $histogram) {
            $this->generatePngChart(
                $histogram->exportAsBuckets(),
                $this->dataDir . '/locust/' . $page . '_histogram.png',
            );
        }
    }

    private function generatePngChart(array $dataSet, string $ouputFilePath,): bool
    {
        $graph = new ezcGraphBarChart();

        $graph->data['ms'] = new ezcGraphArrayDataSet($dataSet);

        $graph->driver = new ezcGraphGdDriver();
        $graph->render(520, 160, $ouputFilePath);

        return $graph->getRenderedFile() !== false;
    }
}
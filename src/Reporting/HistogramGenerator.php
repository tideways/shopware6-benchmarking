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
        $dataSet = array_slice($dataSet, 0, 10);

        $graph = new ezcGraphBarChart();
        $graph->options->font = __DIR__ . '/../../templates/font.ttf';
        $graph->options->font->maxFontSize = 6;
        $graph->options->font->color = '#666666';
        $graph->options->fillLines = false;
        $graph->background->color = '#ffffff';
        $graph->legend = false;

        $graph->palette->majorGridColor = '#cccccc';
        $graph->palette->axisColor = '#cccccc';

        $graph->data['ms'] = new ezcGraphArrayDataSet($dataSet);
        $graph->xAxis->labelCount = count($graph->data['ms']);

        $graph->driver = new ezcGraphGdDriver();
        $graph->render(520, 160, $ouputFilePath);

        return $graph->getRenderedFile() !== false;
    }
}
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
        $graph->options->font = __DIR__ . '/../../templates/font.ttf';
        $graph->options->font->maxFontSize = 6;
        $graph->options->font->color = '#666666';
        $graph->options->fillLines = false;
        $graph->background->color = '#ffffff';
        $graph->legend = false;

        $graph->palette->majorGridColor = '#cccccc';
        $graph->palette->axisColor = '#cccccc';

        $total = array_sum($dataSet);
        $dataSet = array_map(fn ($value) => $value / $total * 100, $dataSet);

        $colors = ['2ce574', 'cdf03a', 'ffe500', 'ff9600', 'ff3924'];

        $i = $sum = 0;
        $color = 4;
        foreach ($dataSet as $value) {
            $sum += $value;

            if ($sum > 95) {
                $color = $colors[$i];
                break;
            }
            $i++;
        }
        $graph->palette->dataSetColor = ['#' . $color];

        $graph->data['Counts'] = new ezcGraphArrayDataSet($dataSet);
        $graph->data['Counts']->highlight = true;
        foreach ($dataSet as $label => $value) {
            if ($value < 0.1) {
                // if the percentage of this bucket is < 0.1% don't show a label
                // because it will be rendered into the label of the bucket.
                continue;
            }
            $graph->data['Counts']->highlightValue[$label] = sprintf('%3.1f%%', $value);
        }
        $graph->yAxis->labelCallback = fn ($value, $value2) => sprintf('%s%%', $value);

        $graph->driver = new ezcGraphGdDriver();
        $graph->render(520, 160, $ouputFilePath);

        return $graph->getRenderedFile() !== false;
    }
}
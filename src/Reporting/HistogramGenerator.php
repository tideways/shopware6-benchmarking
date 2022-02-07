<?php
/**
 * SWBench
 * Copyright (C) 2022 Tideways GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
                $graph->data['Counts']->highlightValue[$label] = ' ';
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
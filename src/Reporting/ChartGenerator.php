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

use ezcGraph;
use ezcGraphChartElementNumericAxis;

class ChartGenerator
{
    public function __construct(private string $dataDir) {}

    public function generateChartsFromTidewaysStats(BenchmarkReport $report, \DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        foreach ($report->pages as $page => $pageReport) {
            $dataSets = $this->transformTidewaysStatsToChartDataSet($pageReport->tideways);
            $dataSets = $this->cropDataToChartRange($dataSets, $start, $end);
            $this->generatePngChart(
                $dataSets,
                $this->dataDir . '/tideways/' . $page . '_performance.png',
                $pageReport->getMaxResponseTimeValueForChart()
            );
        }
    }

    public function generateChartsFromLocustStats(BenchmarkReport $report): void
    {
        foreach ($report->pages as $page => $pageReport) {
            $this->generatePngChart(
                $this->transformTidewaysStatsToChartDataSet($pageReport->locust, withErrors: false),
                $this->dataDir . '/locust/' . $page . '_response_times.png',
                $pageReport->getMaxResponseTimeValueForChart()
            );
        }
    }

    private function transformTidewaysStatsToChartDataSet(TidewaysStats $stats, bool $withErrors = true): array
    {
        $dataSets = ['Response Times' => [], 'Requests' => [], 'Errors' => []];

        foreach ($stats->byTime as $formattedDate => $data) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $formattedDate)->format('Y-m-d H:i:s');
            $dataSets['Response Times'][$date] = $data['percentile_95p'];
            $dataSets['Requests'][$date] = $data['requests'];
            $dataSets['Errors'][$date] = $data['errors'];
        }

        if (!$withErrors) {
            unset($dataSets['Errors']);
        }

        return $dataSets;
    }

    private function cropDataToChartRange(array $dataSets, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        foreach ($dataSets as $label => $data) {
            $keys = array_keys($data);
            $firstKey = $keys[0];
            $lastKey = $keys[count($keys) - 1];

            $data[$start->format("Y-m-d H:i:s")] = $data[$firstKey];
            $data[$end->format("Y-m-d H:i:s")] = $data[$lastKey];

            unset($data[$firstKey]);
            unset($data[$lastKey]);

            ksort($data);

            $dataSets[$label] = $data;
        }

        return $dataSets;
    }

    private function generatePngChart(array $dataSets, string $ouputFilePath, int $maxValue): bool
    {
        $graph = new \ezcGraphLineChart();
        $graph->options->font = __DIR__ . '/../../templates/font.ttf';

        $graph->palette->majorGridColor = '#ffffff';
        $graph->palette->minorGridColor = '#ffffff';
        $graph->palette->axisColor = '#cccccc';

        $dates = array_keys(current($dataSets));

        $graph->xAxis = new \ezcGraphChartElementDateAxis();
        $graph->xAxis->majorGrid = '#ffffff';
        $graph->xAxis->dateFormat = "H:i";
        $graph->xAxis->startDate = strtotime(array_shift($dates));
        $graph->xAxis->endDate = strtotime(array_pop($dates));

        $graph->yAxis->label = 'ms';
        $graph->yAxis->axisSpace = 0.07;
        $graph->yAxis->min = 0;
        $graph->yAxis->max = $this->getNiceNumber($maxValue);
        $graph->yAxis->majorGrid = '#ffffff';
        $graph->yAxis->axisLabelRenderer = new \ezcGraphAxisCenteredLabelRenderer();
        $graph->yAxis->axisLabelRenderer->showZeroValue = true;

        $graph->renderer->options->shortAxis = true;
        $graph->renderer->options->axisEndStyle = \ezcGraph::NO_SYMBOL;

        $graph->palette->dataSetColor = ['#3c8dbc', '#dddddd', '#ff0000'];
        foreach ($dataSets as $label => $data) {
            $graph->data[$label] = new \ezcGraphArrayDataSet($data);
            $graph->data[$label]->fillLine = 180; // HACK: This only works with custom ezcGraph change
        }

        $additionalLabels = ['Requests', 'Errors'];

        $nAxis = new ezcGraphChartElementNumericAxis();
        $nAxis->position = ezcGraph::BOTTOM;
        $nAxis->chartPosition = 1;
        $nAxis->majorGrid = '#ffffff';
        $nAxis->min = 0;
        $nAxis->axisSpace = 0.07;
        $nAxis->label = 'reqs';
        $nAxis->axisLabelRenderer = new \ezcGraphAxisCenteredLabelRenderer();
        $nAxis->axisLabelRenderer->showZeroValue = true;

        foreach ($additionalLabels as $label) {
            if (isset($dataSets[$label])) {
                $graph->additionalAxis[$label] = $nAxis;
                $graph->data[$label]->yAxis = $nAxis;
                $graph->data[$label]->displayType = ezcGraph::LINE;
                $graph->data[$label]->fillLine = false; // HACK: This only works with custom ezcGraph change
            }
        }

        $graph->options->font->maxFontSize = 8;
        $graph->options->font->color = '#666666';
        $graph->options->fillLines = false;
        $graph->background->color = '#ffffff';
        $graph->legend = false;

        // Switch to PNG output for visual consistency with tideways charts
        $graph->driver = new \ezcGraphGdDriver();
        $graph->render(520, 160, $ouputFilePath);

        return $graph->getRenderedFile() !== false;
    }

    protected function getNiceNumber(float $float) : float
    {
        // Get absolute value and save sign
        $abs = abs($float);
        $sign = $float / $abs;

        // Normalize number to a range between 1 and 10
        $log = (int)round(log10($abs), 0);
        $abs /= pow(10, $log);

        // find next nice number
        if ($abs > 5) {
            $abs = 10.;
        } elseif ($abs > 2.5) {
            $abs = 5.;
        } elseif ($abs > 1) {
            $abs = 2.5;
        } else {
            $abs = 1;
        }

        // unnormalize number to original values
        return $abs * pow(10, $log) * $sign;
    }
}

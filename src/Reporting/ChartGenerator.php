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

    /**
     * @param array<string,TidewaysStats> $tidewaysStats
     */
    public function generateChartsFromTidewaysStats(
        array              $tidewaysStats,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): void
    {
        foreach ($tidewaysStats as $page => $data) {
            $dataSets = $this->transformTidewaysStatsToChartDataSet($data);
            $dataSets = $this->cropDataToChartRange($dataSets, $start, $end);
            $this->generatePngChart($dataSets, $this->dataDir . '/tideways/' . $page . '_performance.png');
        }
    }

    public function generateChartsFromLocustStats(
        array              $locustStats,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): void
    {
        foreach ($locustStats as $page => $data) {
            $this->generatePngChart(
                $this->transformLocustStatsToChartDataSet($data),
                $this->dataDir . '/locust/' . $page . '_response_times.png',
            );
        }
    }

    private function transformTidewaysStatsToChartDataSet(TidewaysStats $stats): array
    {
        $dataSets = ['Response Times' => [], 'Requests' => [], 'Errors' => []];

        foreach ($stats->byTime as $formattedDate => $data) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $formattedDate)->format('Y-m-d H:i:s');
            $dataSets['Response Times'][$date] = $data['percentile_95p'];
            $dataSets['Requests'][$date] = $data['requests'];
            $dataSets['Errors'][$date] = $data['errors'];
        }

        return $dataSets;
    }

    private function transformLocustStatsToChartDataSet(array $stats): array
    {
        $dataSets = ['Response Times' => [], 'Requests' => []];

        foreach ($stats as $date => $histogram) {
            $dataSets['Response Times'][$date . ':00'] = $histogram->get95PercentileResponseTime();
            $dataSets['Requests'][$date . ':00'] = $histogram->getRequestCount();
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

    private function generatePngChart(
        array              $dataSets,
        string             $ouputFilePath,
    ): bool
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
        $graph->yAxis->majorGrid = '#ffffff';
        $graph->yAxis->axisLabelRenderer = new \ezcGraphAxisCenteredLabelRenderer();
        $graph->yAxis->axisLabelRenderer->showZeroValue = true;

        $graph->renderer->options->shortAxis = true;
        $graph->renderer->options->axisEndStyle = \ezcGraph::NO_SYMBOL;

        $graph->palette->dataSetColor = ['#3c8dbc', '#cccccc', '#ff0000'];
        foreach ($dataSets as $label => $data) {
            $graph->data[$label] = new \ezcGraphArrayDataSet($data);
            $graph->data[$label]->fillLines = 180; // HACK: This only works with custom ezcGraph change
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
                $graph->data[$label]->fillLines = false; // HACK: This only works with custom ezcGraph change
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
}

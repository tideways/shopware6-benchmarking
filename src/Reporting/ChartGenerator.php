<?php

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
            $this->generatePngChart($dataSets, $this->dataDir . '/tideways/' . $page . '_performance.png', $start, $end);
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
                $start,
                $end
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
        return ["Response Times" => array_combine(
            array_map(
                fn(int $timestamp) => (new \DateTimeImmutable('@' . $timestamp))->format('Y-m-d H:i:s'),
                array_keys($stats)
            ),
            array_map(
                fn(HdrHistogram $value) => $value->get95PercentileResponseTime(),
                array_values($stats)
            ),
        )];
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
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): bool
    {
        $lineWidth = 210;

        $graph = new \ezcGraphLineChart();
        $graph->options->font = __DIR__ . '/../../templates/font.ttf';

        $graph->palette->majorGridColor = '#cccccc';
        $graph->palette->axisColor = '#cccccc';

        $dates = array_keys(current($dataSets));

        $graph->xAxis = new \ezcGraphChartElementDateAxis();
        $graph->xAxis->majorGrid = '#cccccc';
        $graph->xAxis->dateFormat = "H:i";
        $graph->xAxis->startDate = strtotime(array_shift($dates));
        $graph->xAxis->endDate = strtotime(array_pop($dates));

        $graph->yAxis->label = 'ms';
        $graph->yAxis->axisSpace = 0.07;
        $graph->yAxis->min = 0;
        $graph->yAxis->majorGrid = '#cccccc';
        $graph->yAxis->axisLabelRenderer = new \ezcGraphAxisCenteredLabelRenderer();
        $graph->yAxis->axisLabelRenderer->showZeroValue = true;

        $graph->renderer->options->shortAxis = true;
        $graph->renderer->options->axisEndStyle = \ezcGraph::NO_SYMBOL;

        $graph->palette->dataSetColor = ['#00c0ef', '#cccccc', '#ff0000'];
        foreach ($dataSets as $label => $data) {
            $graph->data[$label] = new \ezcGraphArrayDataSet($data);
            $graph->data[$label]->fillLines = 210; // HACK: This only works with custom ezcGraph change
        }

        $additionalLabels = ['Requests', 'Errors'];

        $nAxis = new ezcGraphChartElementNumericAxis();
        $nAxis->position = ezcGraph::BOTTOM;
        $nAxis->chartPosition = 1;
        $nAxis->min = 0;
        $nAxis->axisSpace = 0.07;
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

        $graph->options->font->maxFontSize = 6;
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

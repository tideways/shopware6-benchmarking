<?php

namespace Tideways\Shopware6Benchmarking\Reporting;

class ChartGenerator
{
    public function __construct(private string $dataDir) {}

    public function generateChartsFromTidewaysStats(
        array              $tidewaysStats,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): void
    {
        foreach ($tidewaysStats as $page => $data) {
            $data = $this->transformTidewaysStatsToChartDataSet($data);
            $data = $this->cropDataToChartRange($data, $start, $end);
            $this->generatePngChart($data, $this->dataDir . '/tideways/' . $page . '_performance.png', $start, $end);
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

    private function transformTidewaysStatsToChartDataSet(array $stats): array
    {
        return array_combine(
            array_map(
                function(string $formattedDate) {
                    return \DateTimeImmutable::createFromFormat('Y-m-d H:i', $formattedDate)->format('Y-m-d H:i:s');
                },
                array_keys($stats)
            ),
            array_map(
                fn(array $values) => $values['percentile_95p'],
                array_values($stats)
            ),
        );
    }

    private function transformLocustStatsToChartDataSet(array $stats): array
    {
        return array_combine(
            array_map(
                fn(int $timestamp) => (new \DateTimeImmutable('@' . $timestamp))->format('Y-m-d H:i:s'),
                array_keys($stats)
            ),
            array_map(
                fn(string $value) => intval($value),
                array_values($stats)
            ),
        );
    }

    private function cropDataToChartRange(array $data, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $keys = array_keys($data);
        $firstKey = $keys[0];
        $lastKey = $keys[count($keys) - 1];

        $data[$start->format("Y-m-d H:i:s")] = $data[$firstKey];
        $data[$end->format("Y-m-d H:i:s")] = $data[$lastKey];

        unset($data[$firstKey]);
        unset($data[$lastKey]);

        ksort($data);

        return $data;
    }

    private function generatePngChart(
        array              $data,
        string             $ouputFilePath,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): bool
    {
        $lineWidth = 210;

        $graph = new \ezcGraphLineChart();
        $graph->options->font = __DIR__ . '/../../templates/font.ttf';

        $graph->palette->majorGridColor = '#cccccc';

        $graph->xAxis = new \ezcGraphChartElementDateAxis();
        $graph->xAxis->label = 'UTC';
        $graph->xAxis->font->maxFontSize = 12;
        $graph->xAxis->majorGrid = '#cccccc';
        $graph->xAxis->dateFormat = "H:i";
        $graph->xAxis->startDate = $start->getTimestamp();
        $graph->xAxis->endDate = $end->getTimestamp();

        $graph->yAxis->label = 'ms';
        $graph->yAxis->axisSpace = 0.07;
        $graph->yAxis->min = 0;
        $graph->yAxis->font->maxFontSize = 12;
        $graph->yAxis->majorGrid = '#cccccc';
        $graph->yAxis->axisLabelRenderer = new \ezcGraphAxisCenteredLabelRenderer();
        $graph->yAxis->axisLabelRenderer->showZeroValue = true;

        $graph->renderer->options->shortAxis = true;
        $graph->renderer->options->axisEndStyle = \ezcGraph::NO_SYMBOL;

        $graph->palette->dataSetColor = ['#00c0ef'];
        $graph->data["Response Time"] = new \ezcGraphArrayDataSet($data);
        $graph->options->fillLines = $lineWidth;
        $graph->background->color = '#ffffff';
        $graph->legend = false;

        // Switch to PNG output for visual consistency with tideways charts
        $graph->driver = new \ezcGraphGdDriver();
        $graph->render(520, 160, $ouputFilePath);

        return $graph->getRenderedFile() !== false;
    }
}

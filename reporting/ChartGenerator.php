<?php

namespace Tideways\Shopware6Loadtesting\Reporting;

class ChartGenerator
{
    public function generatePngChart(array $data, string $ouputFilePath): bool
    {
        $lineWidth = 210;

        $graph = new \ezcGraphLineChart();
        $graph->options->font = __DIR__ . '/font.ttf';

        $graph->palette->majorGridColor = '#cccccc';

        $graph->xAxis = new \ezcGraphChartElementDateAxis();
        $graph->xAxis->label = 'UTC';
        $graph->xAxis->font->maxFontSize = 12;
        $graph->xAxis->majorGrid = '#cccccc';

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

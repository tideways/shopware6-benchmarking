<?php

use Symfony\Component\Process\Process;
use Tideways\Shopware6Loadtesting\Reporting\ChartGenerator;
use Tideways\Shopware6Loadtesting\Reporting\LocustStatsParser;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';

$htmlFilePath = __DIR__ . '/generated/report.html';
$pdfFilePath = __DIR__ . '/generated/report.pdf';
$configFilePath = __DIR__ . '/config.json';
$countsFilePath = __DIR__ . '/generated/counts.json';

$locustRunDuration = "2m";

$config = json_decode(file_get_contents($configFilePath), true);
$counts = json_decode(file_get_contents($countsFilePath), true);

$templateVariables = $config;
$templateVariables['counts'] = $counts;

$loader = new FilesystemLoader(__DIR__ . '/reporting/templates');
$twig = new Environment($loader, [
    'cache' => __DIR__ . '/cache/twig',
]);

runLocust($locustRunDuration);

$statsParser = new LocustStatsParser();
$responseTimes = $statsParser->parseLocustStats('shopware64.tideways.io_stats_history.csv');

generateChartsFromLocustStats($responseTimes);

$reportHtml = $twig->render('report.html.twig', $templateVariables);

file_put_contents($htmlFilePath, $reportHtml);

$process = Process::fromShellCommandline(
    sprintf(
        'wkhtmltopdf  ' .
        '-T 20mm ' .
        '-B 20mm ' .
        '-L 15mm ' .
        '-R 15mm ' .
        '--disable-local-file-access --allow ./reporting/ --allow ./generated/ ' .
        '--encoding "utf8" ' .
        '--minimum-font-size 18 ' .
        '--page-width 23cm ' .
        '--header-spacing 10 ' .
        '--header-center "Shopware Benchmark Scenario - Page [page]" ' .
        '--header-font-size 10 ' .
        '%s %s',
        $htmlFilePath,
        $pdfFilePath
    )
);
$process->run();
$exitCode = $process->getExitCode();

if ($exitCode > 0) {
    echo $process->getErrorOutput() . PHP_EOL;
}

exit($exitCode);

function runLocust(string $locustRunDuration): void
{
    $locustProcess = Process::fromShellCommandline(
        sprintf(
            'docker-compose run master ' .
            '-f /mnt/locust/locustfile.py ' .
            '--headless ' .
            '--host=https://shopware64.tideways.io ' .
            '-u 10 -r 1 -t %s ' .
            '--autostart --autoquit 5 ' .
            '--csv=shopware64.tideways.io --csv-full-history ' .
            '--html=shopware64.tideways.io',
            $locustRunDuration
        )
    );
    $locustProcess->setTimeout(null);

    echo "Starting locust run..." . PHP_EOL;
    $locustProcess->run();
    $locustDurationSeconds = microtime(true) - $locustProcess->getStartTime();
    echo sprintf("Complete after %.0f seconds.", $locustDurationSeconds) . PHP_EOL;
}

function transformStatsToChartDataSet(array $stats): array
{
    return array_combine(
        array_map(
            fn(int $timestamp) => (new \DateTimeImmutable('@' . $timestamp))->format('H:i:s'),
            array_keys($stats)
        ),
        array_map(
            fn(string $value) => intval($value),
            array_values($stats)
        ),
    );
}

function generateChartsFromLocustStats(array $locustStats): void
{
    $chartGenerator = new ChartGenerator();

    $listingTimeData = transformStatsToChartDataSet($locustStats['listing-page']);
    $productDetailPageTimeData = transformStatsToChartDataSet($locustStats['product-detail-page']);
    $aggregatedTimeData = transformStatsToChartDataSet($locustStats['Aggregated']);

    $chartGenerator->generatePngChart($listingTimeData, __DIR__ . '/generated/listing-page_response_times.png');
    $chartGenerator->generatePngChart(
        $productDetailPageTimeData,
        __DIR__ . '/generated/product-detail-page_response_times.png'
    );
    $chartGenerator->generatePngChart($aggregatedTimeData, __DIR__ . '/generated/aggregated_response_times.png');
}

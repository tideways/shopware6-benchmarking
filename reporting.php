<?php

use Symfony\Component\Process\Process;
use Tideways\Shopware6Loadtesting\Reporting\ChartGenerator;
use Tideways\Shopware6Loadtesting\Reporting\LocustStatsParser;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require __DIR__ . '/vendor/autoload.php';

$envConfig = parse_ini_file(__DIR__ . '/.env');

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

['start' => $locustRunStart, 'end' => $locustRunEnd] = runLocust($locustRunDuration);

/**
 * @var \DateTimeImmutable $locustRunStart
 */
$tidewaysDataRangeStart = $locustRunStart->setTime(intval($locustRunStart->format('H')), intval($locustRunStart->format('i')));
$tidewaysDataRangeEnd = $locustRunEnd->setTime(
    intval($locustRunEnd->format('H')),
    intval($locustRunEnd->format('i'))
)->modify('+1 minute');

// Allow for tideways data to be processed
sleep(120);

$statsParser = new LocustStatsParser();
$responseTimes = $statsParser->parseLocustStats('shopware64.tideways.io_stats_history.csv');

generateChartsFromLocustStats($responseTimes, $locustRunStart, $locustRunEnd);

$tidewaysPerformanceData = fetchTidewaysPerformanceData(
    $envConfig['TIDEWAYS_API_TOKEN'],
    $tidewaysDataRangeStart,
    $tidewaysDataRangeEnd
);

generateChartsFromTidewaysStats($tidewaysPerformanceData, $locustRunStart, $locustRunEnd);

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

function runLocust(string $locustRunDuration): array
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
    $endTime = microtime(true);
    $locustDurationSeconds = $endTime - $locustProcess->getStartTime();
    echo sprintf("Complete after %.0f seconds.", $locustDurationSeconds) . PHP_EOL;

    return [
        'start' => new \DateTimeImmutable('@'. $locustProcess->getStartTime()),
        'end' => new \DateTimeImmutable('@' . $endTime),
    ];
}

function fetchTidewaysPerformanceData(string $apiToken, \DateTimeImmutable $start, \DateTimeImmutable $end)
{
    $baseUrl = "https://app.tideways.io/apps/api/";
    $client = new GuzzleHttp\Client(['base_uri' => $baseUrl]);
    $url = sprintf(
        "demos-tideways/Shopware6/performance?ts=%s&m=%d",
        $end->format("Y-m-d H:i"),
        $end->diff($start)->i
    );

    $headers = ['Authorization' => sprintf('Bearer %s', $apiToken)];
    $response = $client->request('GET', $url, ['headers' => $headers]);

    $data = json_decode($response->getBody(), true);

    return $data['application']['by_time'];
}

function transformLocustStatsToChartDataSet(array $stats): array
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

function generateChartsFromLocustStats(array $locustStats, \DateTimeImmutable $start, \DateTimeImmutable $end): void
{
    $chartGenerator = new ChartGenerator();

    $listingTimeData = transformLocustStatsToChartDataSet($locustStats['listing-page']);
    $productDetailPageTimeData = transformLocustStatsToChartDataSet($locustStats['product-detail-page']);
    $aggregatedTimeData = transformLocustStatsToChartDataSet($locustStats['Aggregated']);

    $chartGenerator->generatePngChart($listingTimeData, __DIR__ . '/generated/listing-page_response_times.png', $start, $end);
    $chartGenerator->generatePngChart(
        $productDetailPageTimeData,
        __DIR__ . '/generated/product-detail-page_response_times.png',
        $start,
        $end
    );
    $chartGenerator->generatePngChart($aggregatedTimeData, __DIR__ . '/generated/aggregated_response_times.png', $start, $end);
}

function transformTidewaysStatsToChartDataSet(array $stats): array
{
    return array_map(
        fn(array $values) => $values['percentile_95p'],
        $stats
    );
}

function generateChartsFromTidewaysStats(array $tidewaysStats, \DateTimeImmutable $start, \DateTimeImmutable $end): void
{
    $chartGenerator = new ChartGenerator();

    $data = transformTidewaysStatsToChartDataSet($tidewaysStats);

    $chartGenerator->generatePngChart($data, __DIR__ . '/generated/tideways_php_performance.png', $start, $end);
}

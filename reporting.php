<?php

use Symfony\Component\Process\Process;
use Tideways\Shopware6Benchmarking\Reporting\ChartGenerator;
use Tideways\Shopware6Benchmarking\Reporting\LocustStatsParser;
use Tideways\Shopware6Benchmarking\Reporting\TidewaysApiLoader;
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

$statsParser = new LocustStatsParser();
$chartGenerator = new ChartGenerator();
$tidewaysLoader = new TidewaysApiLoader($envConfig['TIDEWAYS_API_TOKEN']);

if (!is_dir(__DIR__ . '/generated/tideways')) {
    mkdir(__DIR__ . '/generated/tideways', 0755, true);
}
if (!is_dir(__DIR__ . '/generated/locust')) {
    mkdir(__DIR__ . '/generated/locust', 0755, true);
}

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

echo "Running benchmark..." . PHP_EOL;
$locustProcess->run();
$endTime = microtime(true);
$locustDurationSeconds = $endTime - $locustProcess->getStartTime();
echo sprintf("Complete after %.0f seconds.", $locustDurationSeconds) . PHP_EOL;

['start' => $locustRunStart, 'end' => $locustRunEnd] = ([
    'start' => new \DateTimeImmutable('@' . $locustProcess->getStartTime()),
    'end' => new \DateTimeImmutable('@' . $endTime),
]);

// Allow for tideways data to be processed
sleep(60);

$locustData = $statsParser->parseLocustStats('shopware64.tideways.io_stats_history.csv');

/** @var \DateTimeImmutable $locustRunStart */
$tidewaysDataRangeStart = $locustRunStart->setTime(
    intval($locustRunStart->format('H')),
    intval($locustRunStart->format('i'))
);
/** @var \DateTimeImmutable $locustRunEnd */
$tidewaysDataRangeEnd = $locustRunEnd->setTime(
    intval($locustRunEnd->format('H')),
    intval($locustRunEnd->format('i'))
)->modify('+1 minute');

$tidewaysData = [];
$tidewaysData['overall'] = $tidewaysLoader->fetchOverallPerformanceData(
    $tidewaysDataRangeStart,
    $tidewaysDataRangeEnd
);
$tidewaysData['product-detail-page'] = $tidewaysLoader->fetchTransactionPerformanceData(
    'Shopware\Storefront\Controller\ProductController::index',
    $tidewaysDataRangeStart,
    $tidewaysDataRangeEnd
);
$tidewaysData['listing-page'] = $tidewaysLoader->fetchTransactionPerformanceData(
    'Shopware\Storefront\Controller\NavigationController::index',
    $tidewaysDataRangeStart,
    $tidewaysDataRangeEnd
);

$chartGenerator->generateChartsFromLocustStats($locustData, $locustRunStart, $locustRunEnd);
$chartGenerator->generateChartsFromTidewaysStats($tidewaysData, $locustRunStart, $locustRunEnd);

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
        '--allow ./generated/tideways --allow ./generated/locust ' .
        '--encoding "utf8" ' .
        '--minimum-font-size 18 ' .
        '--page-width 23cm ' .
        '--header-spacing 10 ' .
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

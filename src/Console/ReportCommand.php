<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tideways\Shopware6Benchmarking\Configuration;
use Tideways\Shopware6Benchmarking\Reporting\BenchmarkReport;
use Tideways\Shopware6Benchmarking\Reporting\ChartGenerator;
use Tideways\Shopware6Benchmarking\Reporting\EmptyPerformanceLoader;
use Tideways\Shopware6Benchmarking\Reporting\HistogramGenerator;
use Tideways\Shopware6Benchmarking\Reporting\LocustStatsParser;
use Tideways\Shopware6Benchmarking\Reporting\PerformanceLoader;
use Tideways\Shopware6Benchmarking\Reporting\TidewaysApiLoader;
use Tideways\Shopware6Benchmarking\Reporting\TidewaysStats;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ReportCommand extends Command
{
    protected static $defaultName = 'generate-report';
    protected static $defaultDescription = 'Generate the report from a completed Locust run.';

    protected function configure(): void
    {
        $this->addOption(
            'config',
            'c',
            InputOption::VALUE_REQUIRED,
            'Scenario configuration file',
            getcwd() . '/default.json'
        );

        $this->addOption(
            'skip-pdf',
            'p',
            InputOption::VALUE_NONE,
            'Skip generation of PDF file using wkhtmltopdf',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = Configuration::fromFile($input->getOption('config'));

        $htmlFilePath = $config->getDataDirectory() . '/report.html';
        $pdfFilePath = $config->getDataDirectory() . '/report.pdf';
        $countsFilePath = $config->getDataDirectory() . '/counts.json';

        $counts = json_decode(file_get_contents($countsFilePath), true);

        $templateVariables = [];
        $templateVariables['config'] = $config;
        $templateVariables['counts'] = $counts;

        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $twig = new Environment($loader, [
            'debug' => true,
            'cache' => sys_get_temp_dir() . '/.swbench-twig-cache',
        ]);

        $statsParser = new LocustStatsParser();
        $chartGenerator = new ChartGenerator($config->getDataDirectory());
        $histogramGenerator = new HistogramGenerator($config->getDataDirectory());
        $tidewaysLoader = $this->createTidewaysApiLoader($config);

        if (!is_dir($config->getDataDirectory() . '/tideways')) {
            mkdir($config->getDataDirectory() . '/tideways', 0755, true);
        }
        if (!is_dir($config->getDataDirectory() . '/locust')) {
            mkdir($config->getDataDirectory() . '/locust', 0755, true);
        }

        $locustStats = $statsParser->parse(
            $config->getDataDirectory() . '/' . $config->getName() . '_requests.csv',
        );

        $tidewaysDataRangeStart = $locustStats->startDate->setTime(
            intval($locustStats->startDate->format('H')),
            intval($locustStats->startDate->format('i'))
        );

        $tidewaysDataRangeEnd = $locustStats->endDate->setTime(
            intval($locustStats->endDate->format('H')),
            intval($locustStats->endDate->format('i'))
        )->modify('+1 minute');

        $report = BenchmarkReport::createShopware6BenchmarkReport();

        foreach ($report->pages as $pageReport) {
            $pageReport->locust = new TidewaysStats(
                byTime: $locustStats->pageByTime[$pageReport->slug],
                requests: $locustStats->pageSummary[$pageReport->slug]->getRequestCount(),
                responseTime: $locustStats->pageSummary[$pageReport->slug]->get95PercentileResponseTime(),
                medianResponseTime: $locustStats->pageSummary[$pageReport->slug]->getMedianResponseTime(),
                errors: 0,
            );

            $pageReport->tideways = $pageReport->transactionName ? $tidewaysLoader->fetchTransactionPerformanceData(
                $pageReport->transactionName,
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            ) : $tidewaysLoader->fetchOverallPerformanceData(
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            );
        }

        $minutes = (($tidewaysDataRangeEnd->getTimestamp() - $tidewaysDataRangeStart->getTimestamp()) / 60);
        $templateVariables['purchases_per_hour'] = round(
            $report->pages['order']->locust->requests / $minutes * 60,
            0
        );
        $templateVariables['requests_per_minute'] = round(
            $locustStats->getTotalRequests() / $minutes, // TODO
            0
        );
        $templateVariables['php_requests_per_minute'] = round(
            $report->pages['overall']->tideways->requests / $minutes,
            0
        );
        $templateVariables['report'] = $report;
        $templateVariables['traces'] = [];

        foreach ($report->pages as $pageName => $pageReport) {
            if (strlen($pageReport->transactionName) === 0) {
                continue;
            }

            $templateVariables['traces'][$pageName] = $tidewaysLoader->fetchTraces(
                $pageReport->transactionName,
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            );
        }

        $chartGenerator->generateChartsFromLocustStats($locustStats->pageByTime);
        $chartGenerator->generateChartsFromTidewaysStats($report, $locustStats->startDate, $locustStats->endDate);

        $histogramGenerator->generateChartsFromLocustStats($locustStats);

        if (file_exists($htmlFilePath)) {
            @copy(
                $htmlFilePath,
                str_replace(
                    '.html',
                    '',
                    $htmlFilePath
                ) . '-' .
                date('Y-m-d-H:i', filemtime($htmlFilePath)) .
                '.html'
            );
        }

        if (file_exists($pdfFilePath)) {
            @copy(
                $pdfFilePath,
                str_replace(
                    '.pdf',
                    '',
                    $pdfFilePath
                ) . '-' .
                date('Y-m-d-H:i', filemtime($pdfFilePath)) .
                '.pdf'
            );
        }

        $reportHtml = $twig->render('report.html.twig', $templateVariables);

        copy(__DIR__ . '/../../templates/shopware_logo_blue.png', $config->getDataDirectory() . '/shopware_logo_blue.png');
        copy(__DIR__ . '/../../templates/tideways.png', $config->getDataDirectory() . '/tideways.png');

        file_put_contents($htmlFilePath, $reportHtml);

        $output->writeln('<info>HTML-Report:</info> <href=file://' . $htmlFilePath . '>' . $htmlFilePath . '</>');

        if ($input->getOption('skip-pdf')) {
            return Command::SUCCESS;
        }

        $process = new Process([
            'wkhtmltopdf',
            '-T', '20mm',
            '-B', '20mm',
            '-L', '15mm',
            '-R', '15mm',
            '--disable-local-file-access',
            '--allow', './templates/',
            '--allow', $config->getDataDirectory(),
            '--allow', $config->getDataDirectory() . '/generated/tideways',
            '--allow', $config->getDataDirectory() . '/generated/locust',
            '--encoding', 'utf8',
            '--minimum-font-size', '8',
            '--page-width', '23cm',
            '--header-spacing', '10',
            '--header-font-size', '10',
            $htmlFilePath,
            $pdfFilePath
        ]);
        $process->run();
        $exitCode = $process->getExitCode();

        if ($exitCode > 0) {
            echo $process->getErrorOutput() . PHP_EOL;
        }

        $output->writeln('<info>PDF-Report:</info> <href=file://' . $pdfFilePath . '>' . $pdfFilePath . '</>');

        return $exitCode;
    }

    protected function createTidewaysApiLoader(Configuration $config): PerformanceLoader
    {
        if (strlen($config->tideways->apiToken) === 0) {
            return new EmptyPerformanceLoader();
        }

        return new TidewaysApiLoader($config->tideways);
    }
}
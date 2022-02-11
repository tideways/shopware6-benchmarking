<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tideways\Shopware6Benchmarking\Configuration;
use Tideways\Shopware6Benchmarking\Reporting\ChartGenerator;
use Tideways\Shopware6Benchmarking\Reporting\EmptyPerformanceLoader;
use Tideways\Shopware6Benchmarking\Reporting\HistogramGenerator;
use Tideways\Shopware6Benchmarking\Reporting\LocustStatsParser;
use Tideways\Shopware6Benchmarking\Reporting\PerformanceLoader;
use Tideways\Shopware6Benchmarking\Reporting\TidewaysApiLoader;
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

        $pageMappings = [
            'product-detail-page' => 'Shopware\Storefront\Controller\ProductController::index',
            'listing-page' => 'Shopware\Storefront\Controller\NavigationController::index',
            'listing-widget-filtered' => 'Shopware\Storefront\Controller\CmsController::category',
            'add-to-cart' => 'Shopware\Storefront\Controller\CartLineItemController::addLineItems',
            'cart-page' => 'Shopware\Storefront\Controller\CheckoutController::cartPage',
            'cart-widget' => 'Shopware\Storefront\Controller\CheckoutController::info',
            'homepage' => 'Shopware\Storefront\Controller\NavigationController::home',
            'search' => 'Shopware\Storefront\Controller\SearchController::search',
            'search-suggest' => 'Shopware\Storefront\Controller\SearchController::ajax',
            'register' => 'Shopware\Storefront\Controller\RegisterController::register',
            'register-page' => 'Shopware\Storefront\Controller\RegisterController::accountRegisterPage',
            'checkout-register-page' => 'Shopware\Storefront\Controller\RegisterController::checkoutRegisterPage',
            'login' => 'Shopware\Storefront\Controller\AuthController::login',
            'order' => 'Shopware\Storefront\Controller\CheckoutController::order',
            'confirm-page' => 'Shopware\Storefront\Controller\CheckoutController::confirmPage',
        ];

        $tidewaysStats = [];
        $tidewaysStats['overall'] = $tidewaysLoader->fetchOverallPerformanceData(
            $tidewaysDataRangeStart,
            $tidewaysDataRangeEnd
        );

        foreach ($pageMappings as $locustPage => $tidewaysTransaction) {
            $tidewaysStats[$locustPage] = $tidewaysLoader->fetchTransactionPerformanceData(
                $tidewaysTransaction,
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            );
        }

        $minutes = (($tidewaysDataRangeEnd->getTimestamp() - $tidewaysDataRangeStart->getTimestamp()) / 60);
        $templateVariables['purchases_per_hour'] = round(
            $tidewaysStats['order']->requests / $minutes * 60,
            0
        );
        $templateVariables['requests_per_minute'] = round(
            $locustStats->getTotalRequests() / $minutes,
            0
        );
        $templateVariables['php_requests_per_minute'] = round(
            $tidewaysStats['overall']->requests / $minutes,
            0
        );
        $templateVariables['tideways'] = $tidewaysStats;
        $templateVariables['locust'] = $locustStats;
        $templateVariables['traces'] = [];

        foreach ($pageMappings as $page => $transactionName) {
            $templateVariables['traces'][$page] = $tidewaysLoader->fetchTraces(
                $transactionName,
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            );
        }

        $chartGenerator->generateChartsFromLocustStats($locustStats->pageByTime, $locustStats->startDate, $locustStats->endDate);
        $chartGenerator->generateChartsFromTidewaysStats($tidewaysStats, $locustStats->startDate, $locustStats->endDate);

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

        copy(__DIR__ . '/../../templates/shopware_logo_blue.svg', $config->getDataDirectory() . '/shopware_logo_blue.svg');
        copy(__DIR__ . '/../../templates/tideways.png', $config->getDataDirectory() . '/tideways.png');

        file_put_contents($htmlFilePath, $reportHtml);

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
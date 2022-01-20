<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tideways\Shopware6Benchmarking\Configuration;
use Tideways\Shopware6Benchmarking\Reporting\ChartGenerator;
use Tideways\Shopware6Benchmarking\Reporting\LocustStatsParser;
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
        $tidewaysLoader = new TidewaysApiLoader($config->tideways->project, $config->tideways->apiToken);

        if (!is_dir($config->getDataDirectory() . '/tideways')) {
            mkdir($config->getDataDirectory() . '/tideways', 0755, true);
        }
        if (!is_dir($config->getDataDirectory() . '/locust')) {
            mkdir($config->getDataDirectory() . '/locust', 0755, true);
        }

        $locustStats = $statsParser->parseLocustStats(
            $config->getDataDirectory() . '/' . $config->getName() . '_stats_history.csv'
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
            'register' => 'Shopware\Storefront\Controller\RegisterController::register',
            'register-page' => 'Shopware\Storefront\Controller\RegisterController::accountRegisterPage',
            'login' => 'Shopware\Storefront\Controller\AuthController::login',
            'order' => 'Shopware\Storefront\Controller\CheckoutController::order',
            'confirm-page' => 'Shopware\Storefront\Controller\CheckoutController::confirmPage',
        ];

        $tidewaysData = [];
        $tidewaysData['overall'] = $tidewaysLoader->fetchOverallPerformanceData(
            $tidewaysDataRangeStart,
            $tidewaysDataRangeEnd
        );

        foreach ($pageMappings as $locustPage => $tidewaysTransaction) {
            $tidewaysData[$locustPage] = $tidewaysLoader->fetchTransactionPerformanceData(
                $tidewaysTransaction,
                $tidewaysDataRangeStart,
                $tidewaysDataRangeEnd
            );
        }

        $templateVariables['tideways'] = $tidewaysData;

        $chartGenerator->generateChartsFromLocustStats($locustStats->pagePercentiles, $locustStats->startDate, $locustStats->endDate);
        $chartGenerator->generateChartsFromTidewaysStats($tidewaysData, $locustStats->startDate, $locustStats->endDate);

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
            '--minimum-font-size', '18',
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
}
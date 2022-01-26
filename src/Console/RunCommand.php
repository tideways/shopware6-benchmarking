<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Tideways\Shopware6Benchmarking\Configuration;
use Tideways\Shopware6Benchmarking\ExecutionMode;
use Tideways\Shopware6Benchmarking\GlobalConfiguration;
use Tideways\Shopware6Benchmarking\Services\RegisterJsonUpdater;
use Tideways\Shopware6Benchmarking\Services\SitemapFixturesDownloader;

class RunCommand extends Command
{
    protected static $defaultName = 'run';
    protected static $defaultDescription = 'Run the Locust Loadtest for benchmarking Shopware based on configuration.';

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
        $workingDir = __DIR__ . '/../../';

        $output->writeln('Update register.json fixture data');

        $registerJsonUpdater = new RegisterJsonUpdater();
        $registerJsonUpdater->update($config, refreshIfExists: true);

        $output->writeln('Update listings and products from sitemap.xml');

        $sitemapDownloader = new SitemapFixturesDownloader();
        $sitemapDownloader->download($config);

        $globalConfiguration = GlobalConfiguration::createFromGlobalDirectory();
        $command = $this->getLocustCommandBasedOnExecutionMode($globalConfiguration->executionMode, $workingDir);

        $locustProcess = new Process(array_merge($command, [
            '--headless',
            '--host=' . $config->scenario->host,
            '-u',
            $config->scenario->concurrentUsers,
            '-r',
            $config->scenario->userSpawnRate,
            '-t',
            $config->scenario->duration,
            '--autostart',
            '--autoquit',
            5,
            '--csv=' . $config->getName(),
            '--csv-full-history',
            '--html=' . $config->getName(),
            '--print-stats',
        ]));
        $locustProcess->setEnv([
            'SWBENCH_NAME' => $config->getName(),
            'SWBENCH_DATA_DIR' => $config->getDataDirectory(),
            'LOCUST_TIDEWAYS_APIKEY' => $config->tideways->apiKey,
            'LOCUST_TIDEWAYS_TRACE_RATE' => $config->tideways->traceSampleRate,
            'LOCUST_RECURRING_USER_RATE' => $config->scenario->recurringUserRate,
            'LOCUST_FILTERER_MIN_FILTERS' => $config->scenario->filtererMinFilters,
            'LOCUST_FILTERER_MAX_FILTERS' => $config->scenario->filtererMaxFilters,
            'LOCUST_FILTERER_VISIT_PRODUCT_RATIO' => $config->scenario->filtererVisitProductRatio,
            'LOCUST_MAX_PAGINATION_SURFING' => $config->scenario->maxPaginationSurfing,
        ]);
        $locustProcess->setWorkingDirectory($workingDir);
        $locustProcess->setTimeout(null);

        $output->writeln("Running benchmark for " . $config->scenario->duration . "...");

        $locustProcess->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        $endTime = microtime(true);
        $locustDurationSeconds = $endTime - $locustProcess->getStartTime();

        $output->writeln(sprintf("Complete after %.0f seconds.", $locustDurationSeconds));

        $resultFiles = ["_exceptions.csv", "_failures.csv", "_stats.csv", "_stats_history.csv"];
        foreach ($resultFiles as $resultFile) {
            $fileName = $config->getName() . '_' . $resultFile;
            @copy($workingDir . '/' . $fileName, $config->getDataDirectory() . '/'. $fileName);
        }

        return Command::SUCCESS;
    }

    private function getLocustCommandBasedOnExecutionMode(ExecutionMode $executionMode, string $workingDir)
    {
        switch ($executionMode) {
            case ExecutionMode::DOCKER:
                return [
                    'docker-compose',
                    'run',
                    'master',
                    '-f',
                    '/mnt/locust/locustfile.py',
                ];
            case ExecutionMode::LOCAL:
                return [
                    'locust',
                    '-f',
                    $workingDir . '/locustfile.py',
                ];
        }
    }
}
<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeConfigCommand extends Command
{
    protected static $defaultName = 'init';
    protected static $defaultDescription = 'Run the Locust Loadtest for benchmarking Shopware based on configuration.';

    protected function configure(): void
    {
        $this->setHelp(<<<HELP

        HELP
        );

        $this->addArgument(
            'host',
            InputArgument::REQUIRED,
            'The host of the Shopware 6 shop to run tests against.'
        );

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
        $file = $input->getOption('config');
        $host = $input->getArgument('host');

        if (file_exists($file)) {
            throw new \LogicException("Configuration file " . $file . " already exists.");
        }

        file_put_contents($file, json_encode([
            'scenario' => [
                'title' => 'Benchmarking ' . $host,
                'duration' => '1m',
                'host' => $host,
            ],
            'shopware' => [
                'version' => 'unknown',
                'plugins' => [],
                'phpVersion' => 'unknown',
                'serverHardware' => 'unknown',
                'httpCacheLifetime' => 'unknown',
                'cacheBackend' => 'unknown',
                'productSearchBackend' => 'unknown',
                'backgroundQueue' => 'unknown',
            ],
        ], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
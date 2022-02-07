<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tideways\Shopware6Benchmarking\Configuration;

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

        $config = Configuration::createNew(title: 'Benchmarking ' . $host, host: $host);
        $config->write($file);

        return Command::SUCCESS;
    }
}
<?php

namespace Tideways\Shopware6Benchmarking\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tideways\Shopware6Benchmarking\ExecutionMode;
use Tideways\Shopware6Benchmarking\GlobalConfiguration;

class GlobalConfigCommand extends Command
{
    protected static $defaultName = 'global-config';
    protected static $defaultDescription = 'Set a global configuration value';

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED);
        $this->addArgument('value', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configuration = GlobalConfiguration::createFromGlobalDirectory();

        $name = $input->getArgument('name');
        $value = $input->getArgument('value');

        switch ($name) {
            case 'executionMode':
                $configuration->executionMode = ExecutionMode::from($value);
                break;

            default:
                throw new \InvalidArgumentException("Unknown config option: ". $name);
        }

        $configuration->save();

        return Command::SUCCESS;
    }
}
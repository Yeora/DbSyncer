<?php

declare(strict_types=1);

namespace Yeora\Command;

use Yeora\Entity\DumperSettings;
use Yeora\Entity\SyncFrom;
use Yeora\Entity\SyncTo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yeora\Helper\AskHelper;
use Yeora\Helper\ConfigHelper;
use Yeora\Helper\DumpHelper;
use Yeora\Helper\InputHelper;
use Yeora\Helper\OutputHelper;
use Yeora\Helper\SyncDatabaseHelper;

class DbSyncer extends Command
{
    private InputInterface $input;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->input = $input;
        OutputHelper::setOutput($output);
        InputHelper::setInput($input);
        AskHelper::setHelper($this->getHelper('question'));
    }

    protected function configure(): void
    {
        $this->setName('sync');
        $this->setDescription('Syncs two Dbs');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'Config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $configFile = $this->input->getOption('config');
            $config     = ConfigHelper::getConfig($configFile);

            $syncFroms     = $config['syncFroms'] ?? [];
            $syncTos       = $config['syncTos'] ?? [];
            $generalConfig = $config['generalConfig'] ?? [];

            $selectedSyncFrom = SyncFrom::getSyncFromData($syncFroms);
            $selectedSyncTos  = SyncTo::getSyncToData($syncTos);

            $selectedSyncFrom = new SyncFrom($selectedSyncFrom);

            $config = new DumperSettings($generalConfig, $selectedSyncFrom->getConfig());

            $selectedSyncFrom->checkMissingHostCredentials();

            if ( ! DumpHelper::makeDbDump($selectedSyncFrom, $config)) {
                return self::FAILURE;
            }

            if ( ! SyncDatabaseHelper::syncDatabase($selectedSyncTos)) {
                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Exception $ex) {
            OutputHelper::printError($ex->getMessage());

            return self::FAILURE;
        }
    }
}
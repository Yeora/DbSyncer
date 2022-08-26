<?php

declare(strict_types=1);

namespace Yeora\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Yeora\Entity\Config;
use Yeora\Entity\Host;
use Yeora\Entity\SyncFrom;
use Yeora\Entity\SyncTo;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Ifsnop\Mysqldump as IMysqldump;
use Symfony\Component\Console\Question\Question;

final class DbSyncer extends Command
{
    private InputInterface $input;
    private OutputInterface $output;
    private string $dumpFile = 'dump.sql';
    /**
     * @var QuestionHelper $helper
     */
    private $helper;

    /**
     * @param Host $host
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $type
     *
     * @return void
     */
    public function checkMissingHostCredentials(
        Host $host,
        InputInterface $input,
        OutputInterface $output,
        string $type = 'SyncFrom'
    ): void {
        if ( ! $host->getUsername()) {
            $question = new Question(
                sprintf(
                    '<question>Please enter the USERNAME for your %s Host (Host=%s Database=%s) : </question>',
                    $type,
                    $host->getHostname(),
                    $host->getDatabase()
                )
            );
            $question->setHidden(false);
            $question->setHiddenFallback(false);
            $username = $this->helper->ask($input, $output, $question);
            $host->setUsername(($username));
        }

        if ( ! $host->getPassword()) {
            $question = new Question(
                sprintf(
                    '<question>Please enter the PASSWORD for your %s Host (Host=%s Database=%s User=%s) : </question>',
                    $type,
                    $host->getHostname(),
                    $host->getDatabase(),
                    $host->getUsername()
                )
            );
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $this->helper->ask($input, $output, $question);
            $host->setPassword(($password));
        }
    }

    /**
     * @param mixed[] $syncTos
     *
     * @return void
     */
    public function syncDatabases(array $syncTos): void
    {
        $this->printQuestion('Syncing Databases');
        $progressBar = new ProgressBar($this->output);
        $progressBar->setFormat('%bar%');
        $progressBar->setMessage('Syncing databases');
        $progressBar->start();

        foreach ($syncTos as $syncToData) {
            $syncTo = new SyncTo($syncToData);

            $this->checkMissingHostCredentials($syncTo, $this->input, $this->output, 'SyncTo');

            $dbh = new PDO(
                'mysql:host=' . $syncTo->getHostname() . ';dbname=' . $syncTo->getDatabase(),
                $syncTo->getUsername(),
                $syncTo->getPassword()
            );

            // Temporary variable, used to store current query
            $templine = '';
            $handle   = fopen($this->dumpFile, 'r');
            if ($handle) {
                while ( ! feof($handle)) { // Loop through each line

                    $fgets = fgets($handle);

                    if ( ! is_string($fgets)) {
                        continue;
                    }

                    $line = trim($fgets);

                    // Skip it if it's a comment
                    if (substr($line, 0, 2) == '--' || $line == '') {
                        continue;
                    }

                    // Add this line to the current segment
                    $templine .= $line;

                    // If it has a semicolon at the end, it's the end of the query
                    if (substr(trim($line), -1, 1) == ';') {
                        // Perform the query
                        $dbh->query($templine);
                        $progressBar->setMessage('Task is in progress...');
                        $progressBar->advance();
                        // Reset temp variable to empty
                        $templine = '';
                    }
                }
                fclose($handle);

                $progressBar->finish();
                $this->printInfo(' Tables synced successfully');
            }
        }
    }

    /**
     * @param SyncFrom $syncFrom
     * @param Config $config
     *
     * @return bool
     */
    public function makeDbDump(SyncFrom $syncFrom, Config $config): bool
    {
        $this->printQuestion('Dumping SQL File');
        $progressBar = new ProgressBar($this->output);
        $progressBar->setFormat('%bar%');
        $progressBar->setMessage('Dumping SQL File');
        $progressBar->start();

        try {
            $dumpSettings = $config->getConfig();

            $dump = new IMysqldump\Mysqldump(
                sprintf('mysql:host=%s;dbname=%s', $syncFrom->getHostname(), $syncFrom->getDatabase()),
                $syncFrom->getUsername() ?? '',
                $syncFrom->getPassword() ?? '',
                $dumpSettings
            );

            $conditions = $syncFrom->getConditions() ?? [];
            $limits     = $syncFrom->getLimits() ?? [];

            $dump->setTransformTableRowHook(function ($tableName, array $row) use ($syncFrom) {
                $tables = $syncFrom->getTables() ?? [];
                foreach ($tables as $innerTableName => $innerTable) {
                    if ($tableName === $innerTableName) {
                        foreach ($innerTable['columns'] as $columnName => $operation) {
                            foreach ($operation as $operationName => $values) {
                                if ($operationName === 'replace') {
                                    foreach ($values as $replaceValue) {
                                        $replacedValue    = str_replace(
                                            (string)$replaceValue['oldValue'],
                                            (string)$replaceValue['value'],
                                            (string)$row[$columnName]
                                        );
                                        $row[$columnName] = $replacedValue;
                                    }
                                }

                                if ($operationName === 'suffix') {
                                    foreach ($values as $appendValue) {
                                        $row[$columnName] = $row[$columnName] . $appendValue['value'];
                                    }
                                }

                                if ($operationName === 'prefix') {
                                    foreach ($values as $appendValue) {
                                        $row[$columnName] = $appendValue['value'] . $row[$columnName];
                                    }
                                }

                                if ($operationName === 'overwrite') {
                                    foreach ($values as $appendValue) {
                                        $row[$columnName] = $appendValue['value'];
                                    }
                                }
                            }
                        }
                    }
                }

                return $row;
            });

            if ($conditions) {
                $dump->setTableWheres($conditions);
            }

            if ($limits) {
                $dump->setTableLimits($limits);
            }

            $dump->setInfoHook(function ($object, $info) use ($progressBar) {
                $progressBar->setMessage('Task is in progress...');
                $progressBar->advance();
            });

            $dump->start($this->dumpFile);

            $progressBar->finish();
            $this->printInfo(' Dump successfully');
        } catch (\Exception $e) {
            $this->printError('Mysqldump-php error: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param mixed[] $syncTos
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed[]
     */
    public function getSyncTos(array $syncTos, InputInterface $input, OutputInterface $output): array
    {
        $syncTosCount = count($syncTos);

        if ($syncTosCount > 1) {
            $syncTosQuestion = [];
            foreach ($syncTos as $key => $syncTo) {
                $syncTosQuestion[] = implode(' ', $syncTo['credentials']);
            }

            $question = new ChoiceQuestion(
                'Please select a SyncTo. (Multiple selection possible by comma separation)',
                $syncTosQuestion,
                '0'
            );
            $question->setMultiselect(true);

            $selectedSyncTos = $this->helper->ask($input, $output, $question);

            $selectedSyncTosArray = [];
            foreach ($selectedSyncTos as $selectedSyncTo) {
                $selectedSyncTosArray[] = $syncTos[array_search($selectedSyncTo, $syncTosQuestion, true)];
            }

            return $selectedSyncTosArray;
        }

        if ($syncTosCount === 1) {
            $credentials = $syncTos[0]['credentials'] ?? null;
            $this->printComment(
                sprintf(
                    'Automatically selected SyncTo Host (Host=%s, Database=%s)',
                    $credentials['hostname'] ?? '',
                    $credentials['database'] ?? ''
                )
            );
        }

        return $syncTos;
    }

    /**
     * @param mixed[] $syncFroms
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return mixed|null
     */
    private function getSyncFrom(array $syncFroms, InputInterface $input, OutputInterface $output)
    {
        $syncFromsCount = count($syncFroms);

        if ($syncFromsCount > 1) {
            $syncFromsQuestion = [];

            foreach ($syncFroms as $key => $syncTo) {
                $syncFromsQuestion[] = implode(' ', $syncTo['credentials']);
            }

            $question = new ChoiceQuestion(
                'Please select a SyncFrom. Multiple selection not possible',
                $syncFromsQuestion,
                '0'
            );

            $question->setMultiselect(false);

            $selectedSyncFrom = $this->helper->ask($input, $output, $question);

            return $syncFroms[array_search($selectedSyncFrom, $syncFromsQuestion, true)];
        }

        if ($syncFromsCount === 1) {
            $credentials = $syncFroms[0]['credentials'] ?? null;
            $this->printComment(
                sprintf(
                    'Automatically selected SyncFrom Host (Host=%s, Database=%s)',
                    $credentials['hostname'] ?? '',
                    $credentials['database'] ?? ''
                )
            );
        }

        return $syncFroms[0] ?? null;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->helper = $this->getHelper('question');
    }

    protected function configure(): void
    {
        $this->setName('sync');
        $this->setDescription('Syncs two Dbs');
        $this->addOption('config', null, InputOption::VALUE_REQUIRED, 'Config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;
        $config       = $this->input->getOption('config');
        $config       = $this->getConfig($config);

        if ( ! $config) {
            $this->printError('Couldn\'t read config file');

            return self::FAILURE;
        }

        $syncFroms     = $config['syncFroms'] ?? [];
        $syncTos       = $config['syncTos'] ?? [];
        $generalConfig = $config['generalConfig'] ?? [];

        $selectedSyncFrom = $this->getSyncFrom($syncFroms, $this->input, $this->output);
        $selectedSyncTos  = $this->getSyncTos($syncTos, $this->input, $this->output);

        if ( ! $selectedSyncFrom) {
            $this->printError('Missing SyncFrom');

            return self::FAILURE;
        }

        if ( ! $selectedSyncTos) {
            $this->printError('Missing SyncTo');

            return self::FAILURE;
        }

        $selectedSyncFrom = new SyncFrom($selectedSyncFrom);

        $config = new Config($generalConfig, $selectedSyncFrom->getConfig());

        $this->checkMissingHostCredentials($selectedSyncFrom, $this->input, $this->output);

        if ( ! $this->makeDbDump($selectedSyncFrom, $config)) {
            $this->printError('Can\'t create Database dump');

            return self::FAILURE;
        }


        $this->syncDatabases($selectedSyncTos);

        return self::SUCCESS;
    }

    /**
     * @param string|null $config
     *
     * @return mixed|null
     */
    protected function getConfig(?string $config = null)
    {
        if ( ! $config) {
            $config = getcwd() . '/DbSyncer.json'; // Default config
            $this->printInfo('No config passed... Reading from default config file DbSyncer.json...');
        }
        if (file_exists($config)) {
            $content = (string)file_get_contents($config);

            return json_decode($content, true);
        }

        return null;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function printInfo(string $message): void
    {
        $this->output->writeln('<info>' . $message . '</info>');
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function printError(string $message): void
    {
        $this->output->writeln('<error>' . $message . '</error>');
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function printComment(string $message): void
    {
        $this->output->writeln('<comment>' . $message . '</comment>');
    }

    /**
     * @param string $message
     *
     * @return void
     */
    protected function printQuestion(string $message): void
    {
        $this->output->writeln('<question>' . $message . '</question>');
    }
}
<?php

namespace Yeora\Helper;

use Ifsnop\Mysqldump\Mysqldump;
use Symfony\Component\Console\Helper\ProgressBar;
use Yeora\Entity\DumperSettings;
use Yeora\Entity\SyncFrom;

class DumpHelper
{
    public static string $dumpFile = 'dump.sql';

    /**
     * @param SyncFrom $syncFrom
     * @param DumperSettings $config
     *
     * @return bool
     */
    public static function makeDbDump(SyncFrom $syncFrom, DumperSettings $config): bool
    {
        $progressBar = new ProgressBar(OutputHelper::$output);
        $progressBar->setFormatDefinition('inProgress', '<question>[%bar%] %message%</question>');
        $progressBar->setFormatDefinition('finished', '<info>[%bar%] %message%</info>');
        $progressBar->setFormatDefinition('error', '<error>[%bar%] %message%</error>');
        $progressBar->setFormat('inProgress');
        $progressBar->setMessage('Dumping database ...');
        $progressBar->start();

        try {
            $dumpSettings = $config->getConfig();

            $dump = new Mysqldump(
                sprintf('mysql:host=%s;dbname=%s;port=%s', $syncFrom->getHostname(), $syncFrom->getDatabase(),$syncFrom->getPort()),
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
                $progressBar->advance();
            });

            $dump->start(self::$dumpFile);

            $progressBar->setFormat('finished');
            $progressBar->setMessage('Database dump successfully' . PHP_EOL);
            $progressBar->finish();
        } catch (\Exception $e) {
            $progressBar->setFormat('error');
            $progressBar->setMessage('Database dump failed' . PHP_EOL);
            $progressBar->finish();

            return false;
        }

        return true;
    }
}
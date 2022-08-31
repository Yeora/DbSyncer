<?php

namespace Yeora\Helper;

use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Yeora\Entity\SyncTo;

class SyncDatabaseHelper
{
    /**
     * @param mixed[] $syncTos
     *
     * @return bool
     */
    public static function syncDatabase(array $syncTos): bool
    {
        $progressBar = new ProgressBar(OutputHelper::$output);
        $progressBar->setFormatDefinition('inProgress', '<question>[%bar%] %message%</question>');
        $progressBar->setFormatDefinition('finished', '<info>[%bar%] %message%</info>');
        $progressBar->setFormatDefinition('config', '<comment>[%bar%] %message%</comment>');
        $progressBar->setFormatDefinition('error', '<error>[%bar%] %message%</error>');
        $progressBar->setFormat('config');
        $progressBar->setMessage('Check Missing Host Credentials ...');
        $progressBar->start();

        try {
            foreach ($syncTos as $syncToData) {
                $syncTo = new SyncTo($syncToData);

                $syncTo->checkMissingHostCredentials();

                $progressBar->setFormat('inProgress');
                $progressBar->setMessage('Syncing database ...');

                $dbh = new PDO(
                    'mysql:host=' . $syncTo->getHostname() . ';dbname=' . $syncTo->getDatabase(),
                    $syncTo->getUsername(),
                    $syncTo->getPassword()
                );

                // Temporary variable, used to store current query
                $templine = '';
                $handle   = fopen(DumpHelper::$dumpFile, 'r');
                if ($handle) {
                    while ( ! feof($handle)) { // Loop through each line

                        $fgets = fgets($handle);

                        if ( ! is_string($fgets)) {
                            continue;
                        }

                        $line = trim($fgets);

                        // Skip it if it's a comment
                        if (strpos($line, '--') === 0 || $line == '') {
                            continue;
                        }

                        // Add this line to the current segment
                        $templine .= $line;

                        // If it has a semicolon at the end, it's the end of the query
                        if (substr(trim($line), -1, 1) === ';') {
                            // Perform the query
                            $dbh->query($templine);
                            $progressBar->advance();
                            // Reset temp variable to empty
                            $templine = '';
                        }
                    }
                    fclose($handle);

                    $progressBar->setFormat('finished');
                    $progressBar->setMessage('Database synced successfully' . PHP_EOL);
                    $progressBar->finish();
                }
            }
        } catch (\Exception $ex) {
            $progressBar->setFormat('error');
            $progressBar->setMessage('Database sync failed' . PHP_EOL);
            $progressBar->finish();

            return false;
        }

        return true;
    }
}
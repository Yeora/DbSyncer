<?php

declare(strict_types=1);

namespace Yeora\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yeora\Helper\AskHelper;
use Yeora\Helper\InputHelper;
use Yeora\Helper\OutputHelper;

class DefaultConfig extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        OutputHelper::setOutput($output);
        InputHelper::setInput($input);
        AskHelper::setHelper($this->getHelper('question'));
    }

    protected function configure(): void
    {
        $this->setName('init');
        $this->setDescription('Creates a default config');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = 'DbSyncer.yaml';

        if (file_exists($file)) {
            $question = new ConfirmationQuestion(
                '<question>' . $file . ' found. Are you sure you want to overwrite [Y/n]?</question> ',
                true,
                '/^(y|j)/i'
            );

            if ( ! AskHelper::ask($question)) {
                OutputHelper::printError('Overwrite aborted.');

                return self::FAILURE;
            }
        }

        $current = $this->getDefaultYamlConfig();
        try {
            file_put_contents($file, $current);
        } catch (Exception $ex) {
            OutputHelper::printError('Overwrite failed. ' . $ex->getMessage());

            return self::FAILURE;
        }

        OutputHelper::printInfo($file . ' was created successfully');
        OutputHelper::printComment('Don\'t forget to change your credentials!');

        return self::SUCCESS;
    }

    private function getDefaultYamlConfig()
    {
        return <<<YAML
---
syncFroms:
  - credentials:
      hostname: YOUR_DB_HOSTNAME
      port: YOUR_DB_PORT
      username: YOUR_DB_USERNAME
      password: YOUR_DB_PASSWORD
      database: YOUR_DB_DATABASENAME
syncTos:
  - credentials:
      hostname: YOUR_DB_HOSTNAME
      port: YOUR_DB_PORT
      username: YOUR_DB_USERNAME
      password: YOUR_DB_PASSWORD
      database: YOUR_DB_DATABASENAME
YAML;
    }
}
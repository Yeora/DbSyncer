<?php

namespace Yeora\Helper;

use Symfony\Component\Console\Output\OutputInterface;

class OutputHelper

{
    public static ?OutputInterface $output = null;

    public static function setOutput(OutputInterface $output): void
    {
        self::$output = $output;
    }

    public static function canPrint(): bool
    {
        return isset(self::$output);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public static function printInfo(string $message): void
    {
        if (self::canPrint()) {
            self::$output->writeln('<info>' . $message . '</info>');
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public static function printError(string $message): void
    {
        if (self::canPrint()) {
            self::$output->writeln('<error>' . $message . '</error>');
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public static function printComment(string $message): void
    {
        if (self::canPrint()) {
            self::$output->writeln('<comment>' . $message . '</comment>');
        }
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public static function printQuestion(string $message): void
    {
        if (self::canPrint()) {
            self::$output->writeln('<question>' . $message . '</question>');
        }
    }
}

<?php

namespace Yeora\Helper;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class AskHelper
{
    public static ?QuestionHelper $helper = null;

    public static function setHelper(QuestionHelper $helper): void
    {
        self::$helper = $helper;
    }

    /**
     * @param Question $question
     *
     * @return mixed
     */
    public static function ask(Question $question)
    {
        return self::$helper->ask(InputHelper::$input, OutputHelper::$output, $question);
    }
}

<?php

namespace Yeora\Helper;

use Symfony\Component\Console\Input\InputInterface;

class InputHelper

{
    public static ?InputInterface $input = null;

    public static function setInput(InputInterface $input): void
    {
        self::$input = $input;
    }
}

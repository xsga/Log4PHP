<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterNewLine extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        return PHP_EOL;
    }
}

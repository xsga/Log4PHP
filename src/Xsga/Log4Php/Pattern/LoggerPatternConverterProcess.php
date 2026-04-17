<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterProcess extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        return getmypid() === false ? '0' : (string)getmypid();
    }
}

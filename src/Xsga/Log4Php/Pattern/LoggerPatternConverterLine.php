<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterLine extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        return $event->getLocationInformation()->getLineNumber();
    }
}

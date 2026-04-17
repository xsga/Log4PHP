<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

/**
 * Custom converter that reads REQUEST_ID dynamically from $_ENV on each log call
 */
final class LoggerPatternConverterRequestId extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        return $_ENV['REQUEST_ID'] ?? '';
    }
}

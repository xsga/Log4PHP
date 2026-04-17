<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterSessionID extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        $sessionId = session_id();

        if ($sessionId === false) {
            return '';
        }

        return $sessionId;
    }
}

<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterLocation extends LoggerPatternConverter
{
    public function convert(LoggerLoggingEvent $event): string
    {
        $out  = $event->getLocationInformation()->getClassName() . '.';
        $out .= $event->getLocationInformation()->getMethodName() . '(';
        $out .= $event->getLocationInformation()->getFileName() . ':';
        $out .= $event->getLocationInformation()->getLineNumber() . ')';

        return $out;
    }
}

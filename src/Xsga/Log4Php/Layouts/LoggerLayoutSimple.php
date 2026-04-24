<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Layouts;

use Xsga\Log4Php\LoggerLayout;
use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerLayoutSimple extends LoggerLayout
{
    public function format(LoggerLoggingEvent $event): string
    {
        $level   = $event->getLevel()->toString();
        $message = $event->getRenderedMessage();

        return $level . ' - ' . $message . PHP_EOL;
    }
}

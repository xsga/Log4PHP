<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

abstract class LoggerLayout extends LoggerConfigurable
{
    public function activateOptions(): void
    {
    }

    public function format(LoggerLoggingEvent $event): string
    {
        return $event->getRenderedMessage();
    }
}

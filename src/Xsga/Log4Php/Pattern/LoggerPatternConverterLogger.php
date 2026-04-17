<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;
use Xsga\Log4Php\Helpers\LoggerUtils;

final class LoggerPatternConverterLogger extends LoggerPatternConverter
{
    private int $length = -1;

    /** @var string[] */
    private array $cache = [];

    public function activateOptions(): void
    {
        if (isset($this->option) && is_numeric($this->option) && ($this->option >= 0)) {
            $this->length = (int)$this->option;
        }
    }

    public function convert(LoggerLoggingEvent $event): string
    {
        $name = $event->getLoggerName();

        if (!isset($this->cache[$name])) {
            $this->cache[$name] = LoggerUtils::shortenClassName($name, $this->length);
        }

        return $this->cache[$name];
    }
}

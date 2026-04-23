<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

use Stringable;

final class LoggerLoggingEvent
{
    private readonly string $renderedMessage;
    private readonly float $timeStamp;
    private readonly LoggerLocationInfo $locationInfo;

    public function __construct(
        private readonly string $loggerName,
        private readonly LoggerLevel $level,
        string|Stringable $message,
        private readonly array $context = []
    ) {
        $this->timeStamp       = microtime(true);
        $this->renderedMessage = $this->setRenderedMessage($message);
        $this->locationInfo    = new LoggerLocationInfo(debug_backtrace());
    }

    public function getLocationInformation(): LoggerLocationInfo
    {
        return $this->locationInfo;
    }

    public function getLevel(): LoggerLevel
    {
        return $this->level;
    }

    public function getLoggerName(): string
    {
        return $this->loggerName;
    }

    public function getRenderedMessage(): string
    {
        return $this->renderedMessage;
    }

    public function getTimeStamp(): float
    {
        return $this->timeStamp;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    private function setRenderedMessage(string|Stringable $message): string
    {
        $renderedMessage = (string)$message;

        if (!empty($this->context)) {
            $renderedMessage = $this->interpolate($renderedMessage, $this->context);
        }

        return $renderedMessage;
    }

    private function interpolate(string $message, array $context): string
    {
        $replacements = [];

        /**
         * @var array<string,mixed> $context
         * @var mixed $value
         */
        foreach ($context as $key => $value) {
            if (is_null($value) || is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replacements['{' . $key . '}'] = (string)$value;
            }
        }

        return strtr($message, $replacements);
    }

    /** @return string[] */
    public function __sleep(): array
    {
        return [
            'loggerName',
            'level',
            'renderedMessage',
            'timeStamp',
            'locationInfo',
            'context'
        ];
    }
}

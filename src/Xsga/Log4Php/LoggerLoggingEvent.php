<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

use Stringable;

final class LoggerLoggingEvent
{
    private readonly string $categoryName;
    private readonly string $renderedMessage;
    private readonly float $timeStamp;
    private readonly LoggerLocationInfo $locationInfo;

    public function __construct(
        Logger|string $logger,
        private readonly LoggerLevel $level,
        string|Stringable $message,
        private readonly array $context = []
    ) {
        $this->timeStamp       = microtime(true);
        $this->renderedMessage = $this->setRenderedMessage($message);
        $this->locationInfo    = $this->setLocationInformation();

        $this->categoryName = match (true) {
            $logger instanceof Logger => $logger->getName(),
            default => strval($logger)
        };
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
        return $this->categoryName;
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

    private function setLocationInformation(): LoggerLocationInfo
    {
        $locationInfo = [];
        $trace        = debug_backtrace();
        $prevHop      = null;

        $hop = array_pop($trace);

        while ($hop !== null) {
            if (isset($hop['class'])) {
                $classNameRaw = $hop['class'];
                $className = strtolower(
                    str_replace(strtolower(LoggerNamespaces::LOG4PHP_NAMESPACE), '', strtolower($classNameRaw))
                );
                $parentClass = get_parent_class($classNameRaw);
                if (
                    !empty($className) && (
                    $className === 'logger' ||
                    ($parentClass !== false && strtolower($parentClass) === 'logger'))
                ) {
                    $locationInfo['line'] = $hop['line'] ?? 0;
                    $locationInfo['file'] = $hop['file'] ?? '';
                    break;
                }
            }

            $prevHop = $hop;
            $hop     = array_pop($trace);
        }

        $locationInfo['class'] = match (true) {
            isset($prevHop['class']) => $prevHop['class'],
            isset($prevHop['function']) => $prevHop['function'],
            default => 'main'
        };

        if (
            isset($prevHop['function']) &&
            $prevHop['function'] !== 'include' &&
            $prevHop['function'] !== 'include_once' &&
            $prevHop['function'] !== 'require' &&
            $prevHop['function'] !== 'require_once'
        ) {
            $locationInfo['function'] = $prevHop['function'];
        } else {
            $locationInfo['function'] = 'main';
        }

        return new LoggerLocationInfo($locationInfo);
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
            'categoryName',
            'level',
            'renderedMessage',
            'timeStamp',
            'locationInfo',
            'context'
        ];
    }
}

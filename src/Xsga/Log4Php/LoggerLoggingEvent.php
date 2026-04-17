<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

use Exception;

final class LoggerLoggingEvent
{
    private ?Logger $logger;
    private string $categoryName;
    private string $renderedMessage = '';
    private float $timeStamp;
    private ?LoggerLocationInfo $locationInfo = null;

    public function __construct(
        private string $fqcn,
        Logger|string $logger,
        protected LoggerLevel $level,
        private mixed $message,
        private array $context = []
    ) {
        $this->timeStamp = microtime(true);

        if ($logger instanceof Logger) {
            $this->logger = $logger;
            $this->categoryName = $logger->getName();
            return;
        }

        $this->categoryName = strval($logger);
    }

    public function getFullQualifiedClassname(): string
    {
        return $this->fqcn;
    }

    public function getLocationInformation(): LoggerLocationInfo
    {
        if ($this->locationInfo === null) {
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

            $this->locationInfo = new LoggerLocationInfo($locationInfo);
        }

        return $this->locationInfo;
    }

    public function getLevel(): LoggerLevel
    {
        return $this->level;
    }

    public function getLogger(): ?Logger
    {
        return $this->logger;
    }

    public function getLoggerName(): string
    {
        return $this->categoryName;
    }

    public function getMessage(): mixed
    {
        return $this->message;
    }

    public function getRenderedMessage(): string
    {
        if (empty($this->renderedMessage) && $this->message !== null) {
            $this->setRenderedMessage();
        }

        return $this->renderedMessage;
    }

    private function setRenderedMessage(): void
    {
        if (is_string($this->message)) {
            $this->renderedMessage = $this->message;
            return;
        }

        $renderer = Logger::getHierarchy()->getRenderer();
        $this->renderedMessage = $renderer->render($this->message);
    }

    public function getTimeStamp(): float
    {
        return $this->timeStamp;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toString(): void
    {
        serialize($this);
    }

    /** @return string[] */
    public function __sleep(): array
    {
        return [
            'fqcn',
            'categoryName',
            'level',
            'message',
            'renderedMessage',
            'timeStamp',
            'locationInfo'
        ];
    }
}

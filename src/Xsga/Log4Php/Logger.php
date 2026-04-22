<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

use Xsga\Log4Php\Configurators\LoggerConfiguratorDefault;
use Psr\Log\LoggerInterface;
use Stringable;

class Logger implements LoggerInterface
{
    private bool $additive = true;
    private ?LoggerLevel $level = null;
    private ?Logger $parent = null;

    /** @var LoggerAppender[] */
    private array $appenders = [];

    private static ?LoggerHierarchy $hierarchy = null;
    private static bool $initialized = false;

    public function __construct(private string $name)
    {
    }

    public function trace(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelTrace(), $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelDebug(), $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelInfo(), $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelNotice(), $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelWarning(), $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelError(), $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelCritical(), $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelAlert(), $message, $context);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LoggerLevel::getLevelEmergency(), $message, $context);
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        if (is_string($level) || is_int($level)) {
            $level = LoggerLevel::toLevel($level);
        }

        if (!$level instanceof LoggerLevel) {
            trigger_error('Invalid log level: ' . var_export($level, true), E_USER_WARNING);
            return;
        }

        $event = new LoggerLoggingEvent($this, $level, $message, $context);

        if ($this->isEnabledFor($level)) {
            $this->callAppenders($event);
        }

        if ($this->parent !== null && $this->additive) {
            $this->parent->logEvent($event);
        }
    }

    public function logEvent(LoggerLoggingEvent $event): void
    {
        if ($this->isEnabledFor($event->getLevel())) {
            $this->callAppenders($event);
        }

        if ($this->parent !== null && $this->additive) {
            $this->parent->logEvent($event);
        }
    }

    public function callAppenders(LoggerLoggingEvent $event): void
    {
        foreach ($this->appenders as $appender) {
            $appender->doAppend($event);
        }
    }

    public function isEnabledFor(LoggerLevel $level): bool
    {
        if (self::getHierarchy()->isDisabled($level)) {
            return false;
        }

        $effectiveLevel = $this->getEffectiveLevel();
        if ($effectiveLevel === null) {
            return false;
        }
        return $level->isGreaterOrEqual($effectiveLevel);
    }

    public function addAppender(LoggerAppender $appender): void
    {
        $this->appenders[$appender->getName()] = $appender;
    }

    public function removeAllAppenders(): void
    {
        foreach (array_keys($this->appenders) as $name) {
            $this->removeAppender((string)$name);
        }
    }

    private function removeAppender(string|LoggerAppender $appender): void
    {
        if ($appender instanceof LoggerAppender) {
            $appender->close();
            unset($this->appenders[$appender->getName()]);
            return;
        }

        if (isset($this->appenders[$appender])) {
            $this->appenders[$appender]->close();
            unset($this->appenders[$appender]);
        }
    }

    public function setAdditivity(bool $additive): void
    {
        $this->additive = $additive;
    }

    public function getEffectiveLevel(): ?LoggerLevel
    {
        for ($logger = $this; $logger !== null; $logger = $logger->getParent()) {
            if ($logger->getLevel() !== null) {
                return $logger->getLevel();
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?Logger
    {
        return $this->parent;
    }

    public function getLevel(): ?LoggerLevel
    {
        return $this->level;
    }

    public function setLevel(?LoggerLevel $level = null): void
    {
        $this->level = $level;
    }

    public function setParent(Logger $logger): void
    {
        $this->parent = $logger;
    }

    public static function getHierarchy(): LoggerHierarchy
    {
        if (static::$hierarchy === null) {
            static::$hierarchy = new LoggerHierarchy(new LoggerRoot());
        }

        return static::$hierarchy;
    }

    public static function getLogger(string $name): Logger
    {
        if (!static::isInitialized()) {
            static::configure();
        }

        return static::getHierarchy()->getLogger($name);
    }

    public static function getRootLogger(): LoggerRoot
    {
        if (!static::isInitialized()) {
            static::configure();
        }

        return static::getHierarchy()->getRootLogger();
    }

    public static function resetConfiguration(): void
    {
        static::getHierarchy()->resetConfiguration();
        static::getHierarchy()->clear();
        static::$initialized = false;
    }

    public static function configure(string|array|null $configuration = null): void
    {
        static::resetConfiguration();

        $configurator = new LoggerConfiguratorDefault();
        $configurator->configure(static::getHierarchy(), $configuration);

        static::$initialized = true;
    }

    public static function isInitialized(): bool
    {
        return static::$initialized;
    }
}

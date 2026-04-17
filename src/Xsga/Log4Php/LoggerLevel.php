<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

final class LoggerLevel
{
    public const int OFF       = 10;
    public const int EMERGENCY = 9;
    public const int ALERT     = 8;
    public const int CRITICAL  = 7;
    public const int ERROR     = 6;
    public const int WARNING   = 5;
    public const int NOTICE    = 4;
    public const int INFO      = 3;
    public const int DEBUG     = 2;
    public const int TRACE     = 1;
    public const int ALL       = 0;

    /** @var LoggerLevel[] */
    private static array $levelMap = [];

    private function __construct(
        private readonly int $level,
        private readonly string $levelStr
    ) {
    }

    public function equals(LoggerLevel $other): bool
    {
        if ($this->level === $other->getLevel()) {
            return true;
        }

        return false;
    }

    public static function getLevelOff(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::OFF])) {
            static::$levelMap[self::OFF] = new LoggerLevel(self::OFF, 'OFF');
        }

        return static::$levelMap[self::OFF];
    }

    public static function getLevelEmergency(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::EMERGENCY])) {
            static::$levelMap[self::EMERGENCY] = new LoggerLevel(self::EMERGENCY, 'EMERGENCY');
        }

        return static::$levelMap[self::EMERGENCY];
    }

    public static function getLevelAlert(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::ALERT])) {
            static::$levelMap[self::ALERT] = new LoggerLevel(self::ALERT, 'ALERT');
        }

        return static::$levelMap[self::ALERT];
    }

    public static function getLevelCritical(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::CRITICAL])) {
            static::$levelMap[self::CRITICAL] = new LoggerLevel(self::CRITICAL, 'CRITICAL');
        }

        return static::$levelMap[self::CRITICAL];
    }

    public static function getLevelError(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::ERROR])) {
            static::$levelMap[self::ERROR] = new LoggerLevel(self::ERROR, 'ERROR');
        }

        return static::$levelMap[self::ERROR];
    }

    public static function getLevelWarning(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::WARNING])) {
            static::$levelMap[self::WARNING] = new LoggerLevel(self::WARNING, 'WARNING');
        }

        return self::$levelMap[self::WARNING];
    }

    public static function getLevelNotice(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::NOTICE])) {
            static::$levelMap[self::NOTICE] = new LoggerLevel(self::NOTICE, 'NOTICE');
        }

        return self::$levelMap[self::NOTICE];
    }

    public static function getLevelInfo(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::INFO])) {
            static::$levelMap[self::INFO] = new LoggerLevel(self::INFO, 'INFO');
        }

        return self::$levelMap[self::INFO];
    }

    public static function getLevelDebug(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::DEBUG])) {
            static::$levelMap[self::DEBUG] = new LoggerLevel(self::DEBUG, 'DEBUG');
        }

        return self::$levelMap[self::DEBUG];
    }

    public static function getLevelTrace(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::TRACE])) {
            static::$levelMap[self::TRACE] = new LoggerLevel(self::TRACE, 'TRACE');
        }

        return self::$levelMap[self::TRACE];
    }

    public static function getLevelAll(): LoggerLevel
    {
        if (!isset(static::$levelMap[self::ALL])) {
            static::$levelMap[self::ALL] = new LoggerLevel(self::ALL, 'ALL');
        }

        return self::$levelMap[self::ALL];
    }

    public function isGreaterOrEqual(LoggerLevel $other): bool
    {
        if ($this->level >= $other->getLevel()) {
            return true;
        }

        return false;
    }

    public function toString(): string
    {
        return $this->levelStr;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toInt(): int
    {
        return $this->level;
    }

    public static function toLevel(int|string $arg, ?LoggerLevel $defaultLevel = null): ?LoggerLevel
    {
        if (is_int($arg)) {
            return static::intLevel($arg, $defaultLevel);
        }

        return static::strLevel($arg, $defaultLevel);
    }

    private static function intLevel(int|string $arg, ?LoggerLevel $defaultLevel = null): ?LoggerLevel
    {
        return match ($arg) {
            self::ALL       => static::getLevelAll(),
            self::TRACE     => static::getLevelTrace(),
            self::DEBUG     => static::getLevelDebug(),
            self::INFO      => static::getLevelInfo(),
            self::NOTICE    => static::getLevelNotice(),
            self::WARNING   => static::getLevelWarning(),
            self::ERROR     => static::getLevelError(),
            self::CRITICAL  => static::getLevelCritical(),
            self::ALERT     => static::getLevelAlert(),
            self::EMERGENCY => static::getLevelEmergency(),
            self::OFF       => static::getLevelOff(),
            default         => $defaultLevel
        };
    }

    private static function strLevel(int|string $arg, ?LoggerLevel $defaultLevel = null): ?LoggerLevel
    {
        return match (strtoupper((string)$arg)) {
            'ALL'       => static::getLevelAll(),
            'TRACE'     => static::getLevelTrace(),
            'DEBUG'     => static::getLevelDebug(),
            'INFO'      => static::getLevelInfo(),
            'NOTICE'    => static::getLevelNotice(),
            'WARNING'   => static::getLevelWarning(),
            'ERROR'     => static::getLevelError(),
            'CRITICAL'  => static::getLevelCritical(),
            'ALERT'     => static::getLevelAlert(),
            'EMERGENCY' => static::getLevelEmergency(),
            'OFF'       => static::getLevelOff(),
            default     => $defaultLevel
        };
    }

    public function getLevel(): int
    {
        return $this->level;
    }
}

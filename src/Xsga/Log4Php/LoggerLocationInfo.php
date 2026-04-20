<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

final class LoggerLocationInfo
{
    private const string LOCATION_INFO_NA = 'NA';

    private readonly string $lineNumber;
    private readonly string $fileName;
    private readonly string $className;
    private readonly string $methodName;

    public function __construct(array $trace)
    {
        $this->lineNumber = isset($trace['line']) ? (string)$trace['line'] : self::LOCATION_INFO_NA;
        $this->fileName   = isset($trace['file']) ? (string)$trace['file'] : self::LOCATION_INFO_NA;
        $this->className  = isset($trace['class']) ? (string)$trace['class'] : self::LOCATION_INFO_NA;
        $this->methodName = isset($trace['function']) ? (string)$trace['function'] : self::LOCATION_INFO_NA;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getLineNumber(): string
    {
        return $this->lineNumber;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }
}

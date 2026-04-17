<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

final class LoggerLocationInfo
{
    public const string LOCATION_INFO_NA = 'NA';

    protected int $lineNumber;
    protected string $fileName;
    protected string $className;
    protected string $methodName;
    protected string $fullInfo = '';

    public function __construct(array $trace)
    {
        $this->lineNumber = isset($trace['line']) ? (int)$trace['line'] : 0;
        $this->fileName   = isset($trace['file']) ? (string)$trace['file'] : '';
        $this->className  = isset($trace['class']) ? (string)$trace['class'] : '';
        $this->methodName = isset($trace['function']) ? (string)$trace['function'] : '';

        $this->fullInfo  = $this->getClassName() . '.' . $this->getMethodName();
        $this->fullInfo .= '(' . $this->getFileName() . ':' . $this->getLineNumber() . ')';
    }

    public function getClassName(): string
    {
        if (empty($this->className)) {
            return self::LOCATION_INFO_NA;
        }

        return $this->className;
    }

    public function getFileName(): string
    {
        if (empty($this->fileName)) {
            return self::LOCATION_INFO_NA;
        }

        return $this->fileName;
    }

    public function getLineNumber(): string
    {
        if ($this->lineNumber === 0) {
            return self::LOCATION_INFO_NA;
        }

        return (string)$this->lineNumber;
    }

    public function getMethodName(): string
    {
        if (empty($this->methodName)) {
            return self::LOCATION_INFO_NA;
        }

        return $this->methodName;
    }

    public function getFullInfo(): string
    {
        if (empty($this->fullInfo)) {
            return self::LOCATION_INFO_NA;
        }

        return $this->fullInfo;
    }
}

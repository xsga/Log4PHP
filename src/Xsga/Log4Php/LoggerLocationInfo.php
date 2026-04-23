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

    /**
     * @param list<array{
     *     file?: string,
     *     line?: int,
     *     function?: string,
     *     class?: class-string,
     *     object?: object,
     *     type?: string,
     *     args?: list<mixed>
     * }> $backTrace
     */
    public function __construct(array $backTrace)
    {
        $trace = $this->getLocationInfoFromBacktrace($backTrace);

        $this->lineNumber = $trace['line'] ?? self::LOCATION_INFO_NA;
        $this->fileName   = $trace['file'] ?? self::LOCATION_INFO_NA;
        $this->className  = $trace['class'] ?? self::LOCATION_INFO_NA;
        $this->methodName = $trace['function'] ?? self::LOCATION_INFO_NA;
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

    /**
     * @param list<array{
     *     file?: string,
     *     line?: int,
     *     function?: string,
     *     class?: class-string,
     *     object?: object,
     *     type?: string,
     *     args?: list<mixed>
     * }> $backTrace
     *
     * @return array{file?: string, line?: string, class: string, function: string}
     */
    private function getLocationInfoFromBacktrace(array $backTrace): array
    {
        $trace   = [];
        $prevHop = null;

        $hop = array_pop($backTrace);

        while ($hop !== null) {
            if (isset($hop['class'])) {
                $classNameRaw = $hop['class'];
                $parentClass = get_parent_class($classNameRaw);
                $className = strtolower(
                    str_replace(strtolower(LoggerNamespaces::LOG4PHP_NAMESPACE), '', strtolower($classNameRaw))
                );

                if (
                    !empty($className) && (
                    $className === 'logger' ||
                    ($parentClass !== false && strtolower($parentClass) === 'logger'))
                ) {
                    $trace['line'] = isset($hop['line']) ? (string)$hop['line'] : '0';
                    $trace['file'] = $hop['file'] ?? '';
                    break;
                }
            }

            $prevHop = $hop;
            $hop     = array_pop($backTrace);
        }

        $trace['class'] = match (true) {
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
            $trace['function'] = $prevHop['function'];
            return $trace;
        }

        $trace['function'] = 'main';

        return $trace;
    }
}

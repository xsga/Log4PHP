<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

use Xsga\Log4Php\Helpers\LoggerNamespaces;

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
        $namespace = strtolower(LoggerNamespaces::LOG4PHP_NAMESPACE);

        $includeFunctions = ['include', 'include_once', 'require', 'require_once'];

        while (($hop = array_pop($backTrace)) !== null) {
            if (isset($hop['class'])) {
                $className = strtolower(str_replace($namespace, '', strtolower($hop['class'])));

                if ($className === 'logger') {
                    $trace['line'] = isset($hop['line']) ? (string)$hop['line'] : '0';
                    $trace['file'] = $hop['file'] ?? '';
                    break;
                }
            }

            $prevHop = $hop;
        }

        $trace['class'] = $prevHop['class'] ?? $prevHop['function'] ?? 'main';

        $function = $prevHop['function'] ?? null;
        if ($function === null || in_array($function, $includeFunctions, true)) {
            $function = 'main';
        }

        $trace['function'] = $function;

        return $trace;
    }
}

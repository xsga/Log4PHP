<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Layouts;

use Xsga\Log4Php\LoggerLayout;
use Xsga\Log4Php\LoggerException;
use Xsga\Log4Php\Helpers\LoggerPatternParser;
use Xsga\Log4Php\LoggerLoggingEvent;
use Xsga\Log4Php\Pattern\LoggerPatternConverter;

final class LoggerLayoutPattern extends LoggerLayout
{
    private const string DEFAULT_CONVERSION_PATTERN = '%date %-5level %logger %message%newline';

    protected string $pattern = self::DEFAULT_CONVERSION_PATTERN;

    /** @var array<string,string> */
    protected static array $defaultConverterMap = [
        'c'         => 'LoggerPatternConverterLogger',
        'lo'        => 'LoggerPatternConverterLogger',
        'logger'    => 'LoggerPatternConverterLogger',
        'C'         => 'LoggerPatternConverterClass',
        'class'     => 'LoggerPatternConverterClass',
        'd'         => 'LoggerPatternConverterDate',
        'date'      => 'LoggerPatternConverterDate',
        'e'         => 'LoggerPatternConverterEnvironment',
        'env'       => 'LoggerPatternConverterEnvironment',
        'F'         => 'LoggerPatternConverterFile',
        'file'      => 'LoggerPatternConverterFile',
        'l'         => 'LoggerPatternConverterLocation',
        'location'  => 'LoggerPatternConverterLocation',
        'L'         => 'LoggerPatternConverterLine',
        'line'      => 'LoggerPatternConverterLine',
        'm'         => 'LoggerPatternConverterMessage',
        'msg'       => 'LoggerPatternConverterMessage',
        'message'   => 'LoggerPatternConverterMessage',
        'M'         => 'LoggerPatternConverterMethod',
        'method'    => 'LoggerPatternConverterMethod',
        'n'         => 'LoggerPatternConverterNewLine',
        'newline'   => 'LoggerPatternConverterNewLine',
        'p'         => 'LoggerPatternConverterLevel',
        'le'        => 'LoggerPatternConverterLevel',
        'level'     => 'LoggerPatternConverterLevel',
        'req'       => 'LoggerPatternConverterRequest',
        'request'   => 'LoggerPatternConverterRequest',
        'rid'       => 'LoggerPatternConverterRequestId',
        'requestid' => 'LoggerPatternConverterRequestId',
        's'         => 'LoggerPatternConverterServer',
        'server'    => 'LoggerPatternConverterServer',
        'ses'       => 'LoggerPatternConverterSession',
        'session'   => 'LoggerPatternConverterSession',
        'sid'       => 'LoggerPatternConverterSessionID',
        'sessionid' => 'LoggerPatternConverterSessionID',
        't'         => 'LoggerPatternConverterProcess',
        'pid'       => 'LoggerPatternConverterProcess',
        'process'   => 'LoggerPatternConverterProcess',
    ];

    /** @var array<string,string> */
    protected array $converterMap = [];

    private ?LoggerPatternConverter $head = null;

    /** @return array<string,string> */
    public static function getDefaultConverterMap(): array
    {
        return static::$defaultConverterMap;
    }

    public function __construct()
    {
        $this->converterMap = static::$defaultConverterMap;
    }

    public function setConversionPattern(string $conversionPattern): void
    {
        if (trim($conversionPattern) === '') {
            $this->pattern = self::DEFAULT_CONVERSION_PATTERN;
            return;
        }

        $this->pattern = $conversionPattern;
    }

    public function activateOptions(): void
    {
        $parser = new LoggerPatternParser($this->pattern, $this->converterMap);

        $this->head = $parser->parse();
    }

    public function format(LoggerLoggingEvent $event): string
    {
        $sbuf = '';
        $converter = $this->head;
        while ($converter !== null) {
            $converter->format($sbuf, $event);
            $converter = $converter->next;
        }

        return $sbuf;
    }
}

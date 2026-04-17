<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Helpers;

use Xsga\Log4Php\LoggerException;
use Xsga\Log4Php\LoggerNamespaces;
use Xsga\Log4Php\Pattern\LoggerPatternConverter;
use Xsga\Log4Php\Pattern\LoggerPatternConverterLiteral;
use ReflectionClass;

final class LoggerPatternParser
{
    private const string ESCAPE_CHAR = '%';

    /** @var array<string,string> */
    private array $converterMap;

    private string $pattern;
    private string $regex;
    private ?LoggerPatternConverter $head = null;
    private ?LoggerPatternConverter $tail = null;

    /** @param array<string,string> $converterMap */
    public function __construct(string $pattern, array $converterMap)
    {
        $this->pattern      = $pattern;
        $this->converterMap = $converterMap;

        $this->regex = '/' . self::ESCAPE_CHAR . '(?P<modifiers>[0-9.-]*)(?P<word>[a-zA-Z]+)(?P<option>{[^}]*})?/';
    }

    public function parse(): ?LoggerPatternConverter
    {
        if (empty($this->pattern)) {
            $this->addLiteral('');
            return $this->head;
        }

        if (empty($this->regex)) {
            throw new LoggerException('Regex pattern cannot be empty.');
        }

        $count = preg_match_all($this->regex, $this->pattern, $matches, PREG_OFFSET_CAPTURE);

        if ($count === false) {
            $error    = error_get_last();
            $errorMsg = $error['message'] ?? '';
            throw new LoggerException("Failed parsing layout pattern: $errorMsg");
        }

        $prevEnd = 0;
        $end     = 0;

        foreach ($matches[0] as $key => $item) {
            $length = strlen($item[0]);
            $start  = $item[1];
            $end    = ($item[1] + $length);

            if ($start > $prevEnd) {
                $this->addLiteral(substr($this->pattern, $prevEnd, ($start - $prevEnd)));
            }

            $word      = $this->extractData($matches, $key, 'word');
            $modifiers = $this->extractData($matches, $key, 'modifiers');
            $option    = $this->extractData($matches, $key, 'option');

            $this->addConverter($word, $modifiers, $option);

            $prevEnd = $end;
        }

        if ($end < strlen($this->pattern)) {
            $this->addLiteral(substr($this->pattern, $end));
        }

        return $this->head;
    }

    private function extractData(array $matches, int $key, string $element): string
    {
        if (
            !empty($matches[$element][$key]) &&
            is_array($matches[$element][$key]) &&
            isset($matches[$element][$key][0])
        ) {
            return (string)$matches[$element][$key][0];
        }

        return '';
    }

    private function addLiteral(string $string): void
    {
        $converter = new LoggerPatternConverterLiteral($string);
        $this->addToChain($converter);
    }

    private function addConverter(string $word, string $modifiers, string $option): void
    {
        $formattingInfo = $this->parseModifiers($modifiers);
        $option         = trim($option, '{} ');

        if (isset($this->converterMap[$word])) {
            $this->addToChain($this->getConverter($word, $formattingInfo, $option));
            return;
        }

        trigger_error("log4php: Invalid keyword \"$word\" in conversion pattern. Ignoring keyword.", E_USER_WARNING);
    }

    private function getConverter(string $word, LoggerFormattingInfo $info, string $option): LoggerPatternConverter
    {
        if (!isset($this->converterMap[$word])) {
            throw new LoggerException('Invalid keyword "%$word" in converison pattern. Ignoring keyword.');
        }

        $converterClass = LoggerNamespaces::PATTERN_NAMESPACE . $this->converterMap[$word];

        if (!class_exists($converterClass)) {
            throw new LoggerException("Class '$converterClass' does not exist.");
        }

        $reflection  = new ReflectionClass($converterClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() !== 2) {
            throw new LoggerException("Class '$converterClass' does not have a valid constructor.");
        }

        $converter = $reflection->newInstance($info, $option);

        if (!($converter instanceof LoggerPatternConverter)) {
            throw new LoggerException("Class '$converterClass' is not an instance of LoggerPatternConverter.");
        }

        return $converter;
    }

    private function addToChain(LoggerPatternConverter $converter): void
    {
        if ($this->head === null) {
            $this->head = $converter;
            $this->tail = $this->head;
            return;
        }

        if ($this->tail !== null) {
            $this->tail->next = $converter;
            $this->tail = $this->tail->next;
        }
    }

    private function parseModifiers(string $modifiers): LoggerFormattingInfo
    {
        $info = new LoggerFormattingInfo();

        if (empty($modifiers)) {
            return $info;
        }

        $pattern = '/^(-?[\d]+)?\.?-?[\d]+$/';

        if (preg_match($pattern, $modifiers) === false) {
            $log = "log4php: Invalid modifier in conversion pattern: [$modifiers]. Ignoring modifier.";
            trigger_error($log, E_USER_WARNING);
            return $info;
        }

        $parts = explode('.', $modifiers);

        if (isset($parts[0]) && $parts[0] !== '') {
            $minPart   = (int)$parts[0];
            $info->min = abs($minPart);

            $info->padLeft = match (true) {
                $minPart > 0 => true,
                default => false,
            };
        }

        if (isset($parts[1]) && $parts[1] !== '') {
            $maxPart   = (int)$parts[1];
            $info->max = abs($maxPart);

            $info->trimLeft = match (true) {
                $maxPart < 0 => true,
                default => false,
            };
        }

        return $info;
    }
}

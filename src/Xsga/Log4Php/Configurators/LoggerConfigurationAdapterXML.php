<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Configurators;

use Xsga\Log4Php\LoggerException;
use SimpleXMLElement;

/**
 * @psalm-type AppenderConfig = array{
 *   class?: null|string,
 *   threshold?: null|string,
 *   layout?: array,
 *   params?: array<string, string|null>,
 * }
 * @psalm-type LoggerConfig = array{
 *   level?: null|string,
 *   additivity?: null|string,
 *   appenders?: list<string|null>
 * }
 * @psalm-type Config = array{
 *   appenders: array<string, AppenderConfig>,
 *   loggers: array<string, LoggerConfig>,
 *   rootLogger?: LoggerConfig,
 *   threshold?: null|string
 * }
 */
final class LoggerConfigurationAdapterXML implements LoggerConfigurationAdapter
{
    /** @var Config */
    private array $config = [
        'appenders' => [],
        'loggers'   => [],
    ];

    public function convert(string $input): array
    {
        $xml = $this->loadXML($input);

        $this->parseConfiguration($xml);

        if (!empty($xml->root)) {
            $this->parseRootLogger($xml->root);
        }

        foreach ($xml->logger as $logger) {
            $this->parseLogger($logger);
        }

        foreach ($xml->appender as $appender) {
            $this->parseAppender($appender);
        }

        return $this->config;
    }

    private function loadXML(string $url): SimpleXMLElement
    {
        if (!file_exists($url)) {
            throw new LoggerException("log4php: File \"$url\" does not exist.");
        }

        libxml_clear_errors();
        $oldValue = libxml_use_internal_errors(true);

        $libxmlOptions = LIBXML_NONET;
        if (defined('LIBXML_NO_XXE')) {
            /** @var int $libxmlNoXxe */
            $libxmlNoXxe = constant('LIBXML_NO_XXE');
            $libxmlOptions |= $libxmlNoXxe;
        }

        $xml = @simplexml_load_file($url, SimpleXMLElement::class, $libxmlOptions);
        if ($xml === false) {
            $errorStr = '';
            foreach (libxml_get_errors() as $error) {
                $errorStr .= $error->message;
            }
            libxml_clear_errors();
            libxml_use_internal_errors($oldValue);
            throw new LoggerException('log4php: Error loading configuration file: ' . trim($errorStr));
        }

        libxml_clear_errors();
        libxml_use_internal_errors($oldValue);

        if ($xml->getName() !== 'configuration') {
            $errorMsg  = "log4php: Invalid configuration file \"$url\": ";
            $errorMsg .= "root element must be <configuration>, got <{$xml->getName()}>.";
            throw new LoggerException($errorMsg);
        }

        return $xml;
    }

    private function parseConfiguration(SimpleXMLElement $xml): void
    {
        $attributes = $xml->attributes();

        if (isset($attributes['threshold'])) {
            $this->config['threshold'] = (string)$attributes['threshold'];
        }
    }

    private function parseAppender(SimpleXMLElement $node): void
    {
        $name = $this->getAttributeValue($node, 'name');

        if ($name === null || $name === '') {
            $errorMsg = 'An <appender> node is missing the required "name" attribute. Skipping appender definition.';
            $this->warn($errorMsg);
            return;
        }

        $appender          = [];
        $appender['class'] = $this->getAttributeValue($node, 'class');

        if (isset($node['threshold'])) {
            $appender['threshold'] = $this->getAttributeValue($node, 'threshold');
        }

        $layoutNodes = $node->xpath('./*[local-name()="layout"]');
        if (is_array($layoutNodes) && isset($layoutNodes[0])) {
            $appender['layout'] = $this->parseLayout($layoutNodes[0]);
        }

        if (count($node->param) > 0) {
            $appender['params'] = $this->parseParameters($node);
        }

        $this->config['appenders'][$name] = $appender;
    }

    private function parseLayout(SimpleXMLElement $node): array
    {
        $layout          = [];
        $layout['class'] = $this->getAttributeValue($node, 'class');

        if (count($node->param) > 0) {
            $layout['params'] = $this->parseParameters($node);
        }

        return $layout;
    }

    /** @return array<string,string> */
    private function parseParameters(SimpleXMLElement $paramsNode): array
    {
        $params = [];
        foreach ($paramsNode->param as $paramNode) {
            if (!isset($paramNode['name'])) {
                $this->warn('A <param> node is missing the required "name" attribute. Skipping parameter.');
                continue;
            }
            $name  = $this->getAttributeValue($paramNode, 'name');
            $value = $this->getAttributeValue($paramNode, 'value');
            if ($name !== null && $value !== null) {
                $params[$name] = $value;
            }
        }
        return $params;
    }

    private function parseRootLogger(SimpleXMLElement $node): void
    {
        $logger = [];

        if (count($node->level) > 0) {
            $logger['level'] = $this->getAttributeValue($node->level, 'value');
        }

        $logger['appenders'] = $this->parseAppenderReferences($node);

        $this->config['rootLogger'] = $logger;
    }

    private function parseLogger(SimpleXMLElement $node): void
    {
        $logger = [];

        $name = $this->getAttributeValue($node, 'name');

        if ($name === null || $name === '') {
            $this->warn('A <logger> node is missing the required "name" attribute. Skipping logger definition.');
            return;
        }

        if (count($node->level) > 0) {
            $logger['level'] = $this->getAttributeValue($node->level, 'value');
        }

        if (isset($node['additivity'])) {
            $logger['additivity'] = $this->getAttributeValue($node, 'additivity');
        }

        $logger['appenders'] = $this->parseAppenderReferences($node);

        if (isset($this->config['loggers'][$name])) {
            $this->warn("Duplicate logger definition \"$name\". Overwriting.");
        }

        $this->config['loggers'][$name] = $logger;
    }

    /** @psalm-return list<string> */
    private function parseAppenderReferences(SimpleXMLElement $node): array
    {
        $refs = [];
        foreach ($node->appender_ref as $ref) {
            $val = $this->getAttributeValue($ref, 'ref');
            if ($val !== null) {
                $refs[] = $val;
            }
        }
        foreach ($node->{'appender-ref'} as $ref) {
            $val = $this->getAttributeValue($ref, 'ref');
            if ($val !== null) {
                $refs[] = $val;
            }
        }
        return $refs;
    }

    private function getAttributeValue(SimpleXMLElement $node, string $name): ?string
    {
        if (isset($node[$name])) {
            return (string)$node[$name];
        }

        return null;
    }

    private function warn(string $message): void
    {
        trigger_error("log4php: [" . get_class($this) . "]: $message", E_USER_WARNING);
    }
}

<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Configurators;

use Exception;
use ReflectionClass;
use Xsga\Log4Php\LoggerConfigurator;
use Xsga\Log4Php\LoggerHierarchy;
use Xsga\Log4Php\LoggerException;
use Xsga\Log4Php\LoggerLevel;
use Xsga\Log4Php\LoggerAppender;
use Xsga\Log4Php\Logger;
use Xsga\Log4Php\LoggerLayout;
use Xsga\Log4Php\Helpers\LoggerNamespaces;
use Xsga\Log4Php\Helpers\LoggerOptionConverter;

/**
 * @psalm-type LayoutConfig = array{
 *   class?: null|string,
 *   params?: array<string, string|null>
 * }
 * @psalm-type AppenderConfig = array{
 *   class?: null|string,
 *   threshold?: null|string,
 *   layout?: LayoutConfig,
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
final class LoggerConfiguratorDefault implements LoggerConfigurator
{
    public const string FORMAT_XML = 'xml';

    /** @var array<string,string> */
    private array $adapters = [
        self::FORMAT_XML => 'LoggerConfigurationAdapterXML',
    ];

    /** @var Config */
    private static array $defaultConfiguration = [
        'threshold'  => 'ALL',
        'rootLogger' => [
            'level'     => 'DEBUG',
            'appenders' => ['default']
        ],
        'appenders'  => [
            'default' => [
                'class' => 'LoggerAppenderDailyFile',
            ]
        ],
        'loggers' => []
    ];

    /** @var LoggerAppender[] */
    private array $appenders = [];

    public function configure(LoggerHierarchy $hierarchy, string|array|null $input = null): void
    {
        /** @var Config $config */
        $config = $this->parse($input);
        $this->doConfigure($hierarchy, $config);
    }

    private function parse(string|array|null $input): array
    {
        if ($input === null || (is_string($input) && trim($input) === '') || (is_array($input) && $input === [])) {
            return static::$defaultConfiguration;
        }

        if (is_array($input)) {
            return $input;
        }

        try {
            /** @var string $input */
            $config = $this->parseFile($input);
        } catch (LoggerException $exception) {
            $this->warn('Configuration failed: ' . $exception->getMessage() . 'Using default configuration.');
            $config = static::$defaultConfiguration;
        }

        return $config;
    }

    /** @return Config */
    private function parseFile(string $url): array
    {
        if (!file_exists($url)) {
            throw new LoggerException("log4php: File not found at \"$url\".");
        }

        $type         = $this->getConfigType($url);
        $adapterClass = LoggerNamespaces::CONFIGURATORS_NAMESPACE . $this->adapters[$type];

        /** @var LoggerConfigurationAdapter */
        $adapter = new $adapterClass();

        /** @var Config $config */
        $config = $adapter->convert($url);

        return $config;
    }

    private function getConfigType(string $url): string
    {
        $info = pathinfo($url);
        $ext  = strtolower($info['extension'] ?? '');

        $format = match ($ext) {
            'xml'   => self::FORMAT_XML,
            default => null
        };

        if ($format === null) {
            throw new LoggerException("log4php: Unsupported configuration file extension \"$ext\".");
        }

        return $format;
    }

    /** @param Config $config */
    private function doConfigure(LoggerHierarchy $hierarchy, array $config): void
    {
        $this->doConfigThreshold($hierarchy, $config);
        $this->doConfigAppenders($config);
        $this->doConfigRootLogger($hierarchy, $config);
        $this->doConfigLoggers($hierarchy, $config);
    }

    /** @param Config $config */
    private function doConfigThreshold(LoggerHierarchy $hierarchy, array $config): void
    {
        if (isset($config['threshold'])) {
            $threshold = LoggerLevel::toLevel($config['threshold']);

            if (isset($threshold)) {
                $hierarchy->setThreshold($threshold);
                return;
            }
            $errorMsg  = 'Invalid threshold value "' . $config['threshold'] . '"';
            $errorMsg .= ' specified. Ignoring threshold definition.';
            $this->warn($errorMsg);
        }
    }

    private function doConfigAppenders(array $config): void
    {
        if (isset($config['appenders']) && is_array($config['appenders'])) {
            /** @var AppenderConfig $appenderConfig */
            foreach ($config['appenders'] as $name => $appenderConfig) {
                /** @var string $name */
                $this->configureAppender($name, $appenderConfig);
            }
        }
    }

    /** @param Config $config */
    private function doConfigRootLogger(LoggerHierarchy $hierarchy, array $config): void
    {
        if (isset($config['rootLogger'])) {
            $this->configureRootLogger($hierarchy, $config['rootLogger']);
        }
    }

    /** @param Config $config */
    private function doConfigLoggers(LoggerHierarchy $hierarchy, array $config): void
    {
        foreach ($config['loggers'] as $loggerName => $loggerConfig) {
            $this->configureOtherLogger($hierarchy, $loggerName, $loggerConfig);
        }
    }

    /** @param AppenderConfig $config */
    private function configureAppender(string $name, array $config): void
    {
        if (!isset($config['class']) || $config['class'] === '') {
            $this->warn("No class given for appender \"$name\". Skipping appender definition.");
            return;
        }

        $class = LoggerNamespaces::APPENDERS_NAMESPACE . $config['class'];

        if (!class_exists($class)) {
            $errorMsg  = "Invalid class \"$class\" given for appender \"$name\". ";
            $errorMsg .= 'Class does not exist. Skipping appender definition.';
            $this->warn($errorMsg);
            return;
        }

        $reflection  = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() !== 1) {
            $errorMsg  = "Appender class \"$class\" specified for appender \"$name\"";
            $errorMsg .= " has a constructor with parameters. Skipping appender definition.";
            $this->warn($errorMsg);
            return;
        }

        $appender = $reflection->newInstance($name);

        if (!($appender instanceof LoggerAppender)) {
            $errorMsg  = "Invalid class \"$class\" given for appender \"$name\"";
            $errorMsg .= ' Not a valid LoggerAppender class. Skipping appender definition.';
            $this->warn($errorMsg);
            return;
        }

        $this->parseAppenderThreshold($name, $config, $appender);
        $this->parseAppenderLayout($config, $appender);
        $this->parseOptions($config, $appender);

        $appender->activateOptions();
        $this->appenders[$name] = $appender;
    }

    /** @param AppenderConfig $config */
    private function parseAppenderThreshold(string $name, array $config, LoggerAppender $appender): void
    {
        if (isset($config['threshold'])) {
            $threshold = LoggerLevel::toLevel($config['threshold']);

            if ($threshold instanceof LoggerLevel) {
                $appender->setThreshold($threshold);
                return;
            }

            $errorMsg  = 'Invalid threshold value "' . $config['threshold'] . "\" specified for appender \"$name\". ";
            $errorMsg .= 'Ignoring threshold definition.';
            $this->warn($errorMsg);
        }
    }

    /** @param AppenderConfig $config */
    private function parseAppenderLayout(array $config, LoggerAppender $appender): void
    {
        if (isset($config['layout'])) {
            $this->createAppenderLayout($appender, $config['layout']);
        }
    }

    /** @param AppenderConfig $config */
    private function parseOptions(array $config, LoggerAppender $appender): void
    {
        if (isset($config['params'])) {
            $this->setOptions($appender, $config['params']);
        }
    }

    /** @param LayoutConfig $config */
    private function createAppenderLayout(LoggerAppender $appender, array $config): void
    {
        $name = $appender->getName();

        if (!isset($config['class']) || $config['class'] === '') {
            $this->warn("Layout class not specified for appender \"$name\". Reverting to default layout.");
            return;
        }

        $class = LoggerNamespaces::LAYOUTS_NAMESPACE . $config['class'];

        if (!class_exists($class)) {
            $errorMsg  = "Nonexistent layout class \"$class\" specified for appender \"$name\". ";
            $errorMsg .= "Reverting to default layout.";
            $this->warn($errorMsg);
            return;
        }

        $reflection  = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor?->getNumberOfParameters() > 0) {
            $errorMsg  = "Layout class \"$class\" specified for appender \"$name\"";
            $errorMsg .= " has a constructor with parameters. Reverting to default layout.";
            $this->warn($errorMsg);
            return;
        }

        $layout = $reflection->newInstance();

        if (!($layout instanceof LoggerLayout)) {
            $errorMsg  = "Invalid layout class \"$class\" specified for appender \"$name\". ";
            $errorMsg .= "Reverting to default layout.";
            $this->warn($errorMsg);
            return;
        }

        if (isset($config['params'])) {
            $this->setOptions($layout, $config['params']);
        }

        $layout->activateOptions();
        $appender->setLayout($layout);
    }

    /** @param LoggerConfig $config */
    private function configureRootLogger(LoggerHierarchy $hierarchy, array $config): void
    {
        $logger = $hierarchy->getRootLogger();
        $this->configureLogger($logger, $config);
    }

    /** @param LoggerConfig $config */
    private function configureOtherLogger(LoggerHierarchy $hierarchy, string $name, array $config): void
    {
        $logger = $hierarchy->getLogger($name);
        $this->configureLogger($logger, $config);
    }

    /** @param LoggerConfig $config */
    private function configureLogger(Logger $logger, array $config): void
    {
        $loggerName = $logger->getName();

        $this->setLoggerLevel($logger, $config, $loggerName);
        $this->linkAppenders($logger, $config, $loggerName);
        $this->setLoggerAdditivity($logger, $config, $loggerName);
    }

    /** @param LoggerConfig $config */
    private function setLoggerLevel(Logger $logger, array $config, string $loggerName): void
    {
        if (isset($config['level'])) {
            $level = LoggerLevel::toLevel($config['level']);
            if (isset($level)) {
                $logger->setLevel($level);
                return;
            }

            $errorMsg  = 'Invalid level value "' . $config['level'] . '" specified for logger "' . $loggerName . '". ';
            $errorMsg .= 'Ignoring level definition.';
            $this->warn($errorMsg);
        }
    }

    /** @param LoggerConfig $config */
    private function linkAppenders(Logger $logger, array $config, string $loggerName): void
    {
        if (isset($config['appenders'])) {
            foreach ($config['appenders'] as $appenderName) {
                if (!is_string($appenderName) || $appenderName === '') {
                    $this->warn("Invalid appender reference for logger \"$loggerName\". Skipping.");
                    continue;
                }

                if (isset($this->appenders[$appenderName])) {
                    $logger->addAppender($this->appenders[$appenderName]);
                    continue;
                }
                $this->warn("Nonexistent appender \"$appenderName\" linked to logger \"$loggerName\".");
            }
        }
    }

    /** @param LoggerConfig $config */
    private function setLoggerAdditivity(Logger $logger, array $config, string $loggerName): void
    {
        if (isset($config['additivity'])) {
            try {
                $additivity = LoggerOptionConverter::toBooleanEx($config['additivity']);
                $logger->setAdditivity($additivity);
            } catch (Exception) {
                $errorMsg  = 'Invalid additivity value "' . $config['additivity'] . '" specified for logger ';
                $errorMsg .= '"' . $loggerName . '". Ignoring additivity setting.';
                $this->warn($errorMsg);
            }
        }
    }

    /** @param array<string,string|null> $options */
    private function setOptions(object $object, array $options): void
    {
        foreach ($options as $name => $value) {
            $setter = 'set' . $name;
            if (method_exists($object, $setter)) {
                $object->$setter($value);
                continue;
            }
            $class = get_class($object);
            $this->warn("Nonexistent option \"$name\" specified on \"$class\". Skipping.");
        }
    }

    private function warn(string $message): void
    {
        trigger_error("log4php: [" . get_class($this) . "]: $message", E_USER_WARNING);
    }
}

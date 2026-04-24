<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

final class LoggerHierarchy
{
    /** @var array<string,Logger> */
    private array $loggers = [];

    private LoggerLevel $threshold;

    public function __construct(private LoggerRoot $root)
    {
        $this->setThreshold(LoggerLevel::getLevelAll());
    }

    public function clear(): void
    {
        $this->loggers = [];
    }

    public function getLogger(string $name): Logger
    {
        if (!isset($this->loggers[$name])) {
            $logger = new Logger($name);
            $this->loggers[$name] = $logger;
            $this->updateParents($logger);
            $this->updateChildren($logger);
        }

        return $this->loggers[$name];
    }

    private function updateParents(Logger $logger): void
    {
        $parts = explode('.', $logger->getName());
        array_pop($parts);

        while (!empty($parts)) {
            $candidateName = implode('.', $parts);
            if (isset($this->loggers[$candidateName])) {
                $logger->setParent($this->loggers[$candidateName]);
                return;
            }
            array_pop($parts);
        }

        $logger->setParent($this->root);
    }

    private function updateChildren(Logger $newLogger): void
    {
        $prefix = $newLogger->getName() . '.';

        foreach ($this->loggers as $existingName => $existingLogger) {
            if ($existingName !== $newLogger->getName() && str_starts_with($existingName, $prefix)) {
                $this->updateParents($existingLogger);
            }
        }
    }

    public function getRootLogger(): LoggerRoot
    {
        return $this->root;
    }

    public function getThreshold(): LoggerLevel
    {
        return $this->threshold;
    }

    public function isDisabled(LoggerLevel $level): bool
    {
        if ($this->threshold->toInt() > $level->toInt()) {
            return true;
        }

        return false;
    }

    public function resetConfiguration(): void
    {
        $root = $this->getRootLogger();

        $root->setLevel(LoggerLevel::getLevelDebug());
        $this->setThreshold(LoggerLevel::getLevelAll());
        $this->shutdown();

        foreach ($this->loggers as $logger) {
            $logger->setLevel(null);
            $logger->setAdditivity(true);
            $logger->removeAllAppenders();
        }
    }

    public function setThreshold(LoggerLevel $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function shutdown(): void
    {
        $this->root->removeAllAppenders();

        foreach ($this->loggers as $logger) {
            $logger->removeAllAppenders();
        }
    }
}

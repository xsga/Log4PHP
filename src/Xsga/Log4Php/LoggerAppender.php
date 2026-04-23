<?php

declare(strict_types=1);

namespace Xsga\Log4Php;

abstract class LoggerAppender extends LoggerConfigurable
{
    protected bool $closed = false;
    protected ?LoggerLayout $layout = null;
    protected ?LoggerLevel $threshold = null;

    public function __construct(private string $name = '')
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    public function doAppend(LoggerLoggingEvent $event): void
    {
        if ($this->closed) {
            return;
        }

        if (!$this->isAsSevereAsThreshold($event->getLevel())) {
            return;
        }

        $this->append($event);
    }

    public function setLayout(LoggerLayout $layout): void
    {
        $this->layout = $layout;
    }

    public function getLayout(): ?LoggerLayout
    {
        return $this->layout;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getThreshold(): ?LoggerLevel
    {
        return $this->threshold;
    }

    public function setThreshold(LoggerLevel $threshold): void
    {
        $this->setLevel('threshold', $threshold);
    }

    public function isAsSevereAsThreshold(LoggerLevel $level): bool
    {
        if ($this->threshold === null) {
            return true;
        }

        $threshold = $this->getThreshold();
        if ($threshold === null) {
            return true;
        }

        return $level->isGreaterOrEqual($threshold);
    }

    public function activateOptions(): void
    {
        $this->closed = false;
    }

    abstract protected function append(LoggerLoggingEvent $event): void;

    public function close(): void
    {
        $this->closed = true;
    }

    protected function warn(mixed $message): void
    {
        $id = get_class($this) . (empty($this->name) ? '' : ": $this->name");
        trigger_error("log4php: [$id]: $message", E_USER_WARNING);
    }
}

<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Appenders;

use Xsga\Log4Php\LoggerAppender;
use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerAppenderConsole extends LoggerAppender
{
    private const string TARGET_STDOUT = 'stdout';
    private const string TARGET_STDERR = 'stderr';
    private const string TARGET_PHP    = 'php';

    protected string $target = self::TARGET_STDOUT;

    /** @var null|resource|closed-resource */
    private $stream = null;

    public function append(LoggerLoggingEvent $event): void
    {
        $message = $this->layout?->format($event);

        if ($message === null || $message === '') {
            return;
        }

        if ($this->target === self::TARGET_PHP) {
            error_log($message);
            return;
        }

        $stream = $this->getStreamResource();

        if ($stream === null) {
            $this->warn('Failed opening console stream. Event discarded.');
            return;
        }

        fwrite($stream, $message);
    }

    public function setTarget(string $target): void
    {
        $value = strtolower(trim($target));

        if (
            $value !== self::TARGET_STDOUT
            && $value !== self::TARGET_STDERR
            && $value !== self::TARGET_PHP
        ) {
            $this->warn("Invalid target [$target]. Falling back to stdout.");
            $value = self::TARGET_STDOUT;
        }

        $this->setString('target', $value);
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = null;
        parent::close();
    }

    /** @return null|resource */
    private function getStreamResource()
    {
        if (is_resource($this->stream)) {
            return $this->stream;
        }

        $path = $this->target === self::TARGET_STDERR ? 'php://stderr' : 'php://stdout';
        $stream = fopen($path, 'ab');
        if ($stream === false) {
            return null;
        }

        $this->stream = $stream;
        return $this->stream;
    }
}

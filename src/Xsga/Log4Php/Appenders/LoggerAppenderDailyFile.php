<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Appenders;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerAppenderDailyFile extends LoggerAppenderFile
{
    protected string $datePattern = 'Ymd';
    protected string $currentDate = '';

    public function activateOptions(): void
    {
        parent::activateOptions();

        if (empty($this->datePattern)) {
            $this->warn("Required parameter 'datePattern' not set. Closing appender.");
            $this->closed = true;
        }
    }

    public function append(LoggerLoggingEvent $event): void
    {
        $eventDate = $this->getDate($event->getTimestamp());

        if ($this->currentDate === '') {
            $this->currentDate = $eventDate;
            parent::append($event);
            return;
        }

        if ($this->currentDate !== $eventDate) {
            if (is_resource($this->fp)) {
                fclose($this->fp);
                $this->fp = null;
            }
            $this->currentDate = $eventDate;
        }

        parent::append($event);
    }

    protected function getDate(?float $timestamp = null): string
    {
        return date($this->datePattern, (int)$timestamp);
    }

    protected function getTargetFile(): string
    {
        return str_replace('%s', $this->currentDate, $this->file);
    }

    public function setDatePattern(string $datePattern): void
    {
        $this->setString('datePattern', $datePattern);
    }
}

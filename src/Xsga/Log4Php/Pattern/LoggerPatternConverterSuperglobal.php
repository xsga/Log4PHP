<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

abstract class LoggerPatternConverterSuperglobal extends LoggerPatternConverter
{
    protected string $name;
    protected string $value = '';

    public function activateOptions(): void
    {
        $class = get_class($this);

        if (isset($this->option) && ($this->option !== '')) {
            $key = $this->option;
        }

        if (!isset($GLOBALS[$this->name])) {
            trigger_error("log4php: $class: Cannot find superglobal variable " . $this->name, E_USER_WARNING);
            return;
        }

        /** @var string[] $source */
        $source = $GLOBALS[$this->name];

        if (isset($key) && is_string($key)) {
            if (isset($source[$key])) {
                $this->value = $source[$key];
                if (empty($this->value)) {
                    $this->value = 'empty';
                }
                return;
            }
            $this->value = 'empty';
            return;
        }

        trigger_error("log4php: $class: key not provided for superglobal $" . $this->name, E_USER_WARNING);
    }

    public function convert(LoggerLoggingEvent $event): string
    {
        return $this->value;
    }
}

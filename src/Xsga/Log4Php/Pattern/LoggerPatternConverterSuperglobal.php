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
        if (isset($this->option) && ($this->option !== '')) {
            $key = $this->option;
        }

        if (!isset($GLOBALS[$this->name])) {
            $class = get_class($this);
            trigger_error("log4php: $class: Cannot find superglobal variable $" . $this->name, E_USER_WARNING);
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

        $values = [];
        foreach ($source as $key => $value) {
            $values[] = $key . '=' . $value;
        }
        $this->value = implode(', ', $values);
    }

    public function convert(LoggerLoggingEvent $event): string
    {
        return $this->value;
    }
}

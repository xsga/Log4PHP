<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\Helpers\LoggerFormattingInfo;
use Xsga\Log4Php\LoggerLoggingEvent;

abstract class LoggerPatternConverter
{
    public ?LoggerPatternConverter $next = null;
    protected ?LoggerFormattingInfo $formattingInfo;
    protected ?string $option;

    public function __construct(?LoggerFormattingInfo $formattingInfo = null, ?string $option = null)
    {
        $this->formattingInfo = $formattingInfo;
        $this->option         = $option;

        $this->activateOptions();
    }

    public function activateOptions(): void
    {
    }

    abstract public function convert(LoggerLoggingEvent $event): string;

    public function format(string &$sbuf, LoggerLoggingEvent $event): void
    {
        $string = $this->convert($event);

        if (!isset($this->formattingInfo)) {
            $sbuf .= $string;
            return;
        }

        $fi = $this->formattingInfo;

        if ($string === '') {
            if ($fi->min > 0) {
                $sbuf .= str_repeat(' ', $fi->min);
            }
            return;
        }

        $len = strlen($string);

        if ($len > $fi->max) {
            $sbuf .= match ($fi->trimLeft) {
                true  => substr($string, ($len - $fi->max), $fi->max),
                false => substr($string, 0, $fi->max)
            };
            return;
        }

        if ($len < $fi->min) {
            $sbuf .= match ($fi->padLeft) {
                true  => str_repeat(' ', ($fi->min - $len)) . $string,
                false => $string . str_repeat(' ', ($fi->min - $len))
            };
            return;
        }

        $sbuf .= $string;
    }
}

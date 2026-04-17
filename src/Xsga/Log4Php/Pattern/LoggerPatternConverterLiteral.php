<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Pattern;

use Xsga\Log4Php\LoggerLoggingEvent;

final class LoggerPatternConverterLiteral extends LoggerPatternConverter
{
    public function __construct(private string $literalValue)
    {
    }

    public function convert(LoggerLoggingEvent $event): string
    {
        return $this->literalValue;
    }
}

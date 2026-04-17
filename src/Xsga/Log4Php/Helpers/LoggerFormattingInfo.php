<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Helpers;

final class LoggerFormattingInfo
{
    public int $min = 0;
    public int $max = PHP_INT_MAX;
    public bool $padLeft = true;
    public bool $trimLeft = false;
}

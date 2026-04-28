<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Helpers;

final class LoggerUtils
{
    public static function shortenClassName(string $name, int $length): string
    {
        if ($length < 0) {
            return $name;
        }

        $name = str_replace('.', '\\', $name);
        $name = trim($name, ' \\');

        $fragments = explode('\\', $name);
        $count     = count($fragments);

        if ($length === 0) {
            return array_pop($fragments);
        }

        if ($length >= $count) {
            return $name;
        }

        return implode('\\', array_slice($fragments, $count - $length));
    }
}

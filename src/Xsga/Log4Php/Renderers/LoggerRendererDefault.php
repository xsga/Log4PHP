<?php

declare(strict_types=1);

namespace Xsga\Log4Php\Renderers;

final class LoggerRendererDefault implements LoggerRenderer
{
    public function render(mixed $input): string
    {
        return print_r($input, true);
    }
}
